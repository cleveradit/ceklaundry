<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Services\AccountService;
use App\Services\AuthRateLimiter;
use App\Services\LifecycleService;
use App\Support\Input;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthenticationController extends Controller
{
    public function loginForm()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request, AuthRateLimiter $limiter)
    {
        $data = $request->validate(['email' => ['required', 'string', 'max:150'], 'password' => ['required', 'string', 'max:72']]);
        $email = Input::email($data['email']);
        $limiter->attempt('login', $email, $request->ip());
        $user = User::query()->where('email', $email)->first();
        if (! $user || strlen($data['password']) > 72 || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Email atau password tidak cocok.']);
        }
        $business = $user->business_id ? Business::query()->findOrFail($user->business_id) : null;
        if (! $user->is_active || ($business && (! app(LifecycleService::class)->panelAllowed($business) || $business->is_demo)) || ($user->role === 'admin' && ! DB::table('branches')->where('id', $user->branch_id)->where('business_id', $user->business_id)->where('is_active', true)->exists())) {
            throw ValidationException::withMessages(['email' => 'Akun, cabang, atau akses bisnis tidak aktif. Hubungi pengelola.']);
        }
        Auth::login($user, false);
        $request->session()->regenerate();

        return redirect($user->must_change_password ? '/password/change' : $this->home($user));
    }

    public function home(User $user): string
    {
        return match ($user->role) {
            'developer' => '/dev', 'owner' => '/owner', default => '/app'
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Inertia::clearHistory();

        return redirect('/login');
    }

    public function changeForm()
    {
        return Inertia::render('Auth/ChangePassword');
    }

    public function change(Request $request, AccountService $accounts)
    {
        $data = $request->validate(['current_password' => ['required', 'string'], 'password' => [...Input::passwordRules(), 'confirmed']]);
        $accounts->changePassword($request->user(), $data['current_password'], $data['password'], $request->session()->getId());
        $request->session()->regenerate();
        Auth::setUser($request->user()->fresh());

        return redirect($this->home($request->user()))->with('success', 'Password berhasil diganti.');
    }

    public function forgotForm()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function forgot(Request $request, AccountService $accounts, AuthRateLimiter $limiter)
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:150']]);
        $limiter->attempt('reset', Input::email($data['email']), $request->ip());
        $accounts->requestReset($data['email']);

        return back()->with('success', 'Jika akun dapat menerima email, tautan reset akan dikirim. Periksa kotak masuk Anda.');
    }

    public function resetForm(Request $request, string $token)
    {
        return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function reset(Request $request, AccountService $accounts)
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:150'], 'token' => ['required', 'string', 'size:64'], 'password' => [...Input::passwordRules(), 'confirmed']]);
        $accounts->resetPassword($data['email'], $data['token'], $data['password']);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Inertia::clearHistory();

        return redirect('/login')->with('success', 'Password berhasil direset. Silakan masuk kembali.');
    }
}

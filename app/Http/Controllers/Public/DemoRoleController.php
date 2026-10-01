<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\DemoSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DemoRoleController extends Controller
{
    public function switch(Request $request, DemoSessionService $sessions)
    {
        abort_if(count($request->except('_token')) > 0, 422, 'Pilihan akun demo tidak valid.');
        $user = $request->user();
        $business = Business::query()->findOrFail($user->business_id);
        abort_unless($business->is_demo, 403, 'Pergantian peran hanya tersedia pada demo.');
        [$owner, $admin] = $sessions->assert($request, $user, $business);
        $target = $user->role === 'owner' ? $admin : $owner;
        Auth::login($target, false);
        $request->session()->regenerate();
        $request->session()->put('demo_pair', [
            'business_id' => $business->id, 'owner_id' => $owner->id, 'admin_id' => $admin->id,
        ]);
        Inertia::clearHistory();

        return redirect($target->role === 'owner' ? '/owner' : '/app');
    }
}

<?php

namespace App\Services;

use App\Jobs\SendPasswordReset;
use App\Models\Branch;
use App\Models\Business;
use App\Models\User;
use App\Support\Input;
use App\Tenancy\TenantContext;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountService
{
    public function validateAccount(array $data, ?int $ignore = null): array
    {
        $data['email'] = Input::email((string) ($data['email'] ?? ''));
        if ($ignore) {
            unset($data['password']);
        }

        return Validator::make($data, [
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('users', 'email')->ignore($ignore)],
            'password' => $ignore ? ['sometimes', ...Input::passwordRules()] : Input::passwordRules(),
        ])->validate();
    }

    public function revoke(User $user, ?string $exceptSession = null, ?string $oldEmail = null): void
    {
        $sessions = DB::table('sessions')->where('user_id', $user->id);
        if ($exceptSession) {
            $sessions->where('id', '!=', $exceptSession);
        }
        $sessions->delete();
        DB::table('password_reset_tokens')->whereIn('email', array_unique(array_filter([$user->email, $oldEmail])))->delete();
    }

    private function withSecurityLock(User $user, Closure $callback): mixed
    {
        return DB::transaction(function () use ($user, $callback) {
            $business = $user->business_id ? Business::query()->lockForUpdate()->findOrFail($user->business_id) : null;
            $fresh = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($fresh->business_id !== $user->business_id) {
                abort(409, 'Akun berubah. Silakan coba lagi.');
            }

            return $business ? app(TenantContext::class)->run($business->id, fn () => $callback($fresh, $business)) : $callback($fresh, null);
        }, 3);
    }

    public function changePassword(User $user, string $current, string $password, string $sessionId): void
    {
        Validator::make(['password' => $password], ['password' => Input::passwordRules()])->validate();
        $this->withSecurityLock($user, function (User $fresh, ?Business $business) use ($current, $password, $sessionId) {
            abort_unless($fresh->is_active && (! $business || app(LifecycleService::class)->panelAllowed($business)), 403);
            if ($fresh->role === 'admin') {
                abort_unless(Branch::query()->whereKey($fresh->branch_id)->where('is_active', true)->exists(), 403, 'Cabang tidak aktif.');
            }
            if (! Hash::check($current, $fresh->password)) {
                throw ValidationException::withMessages(['current_password' => 'Password saat ini tidak cocok.']);
            }
            $fresh->forceFill(['password' => $password, 'must_change_password' => false])->save();
            $this->revoke($fresh, $sessionId);
        });
    }

    public function requestReset(string $email): void
    {
        $user = User::query()->where('email', Input::email($email))->first();
        if (! $user) {
            return;
        }
        $this->withSecurityLock($user, function (User $fresh, ?Business $business) use ($email) {
            if ($fresh->email !== Input::email($email) || ! app(OutboundGuard::class)->allows($business, now())) {
                return;
            }
            $token = Str::random(64);
            DB::table('password_reset_tokens')->updateOrInsert(['email' => $fresh->email], ['token' => Hash::make($token), 'created_at' => now()]);
            SendPasswordReset::dispatch($fresh->id, $fresh->email, $token, now()->addMinutes(60)->toDateTimeString(), $fresh->business_id)->beforeCommit();
        });
    }

    public function resetPassword(string $email, string $token, string $password): void
    {
        Validator::make(['password' => $password], ['password' => Input::passwordRules()])->validate();
        $user = User::query()->where('email', Input::email($email))->first();
        if (! $user) {
            $this->invalidToken();
        }
        $this->withSecurityLock($user, function (User $fresh, ?Business $business) use ($email, $token, $password) {
            $row = DB::table('password_reset_tokens')->where('email', $fresh->email)->first();
            if ($fresh->email !== Input::email($email) || ! $row || ! app(OutboundGuard::class)->allows($business, $row->created_at) || now()->greaterThanOrEqualTo(Carbon::parse($row->created_at)->addMinutes(60)) || ! Hash::check($token, $row->token)) {
                $this->invalidToken();
            }
            $fresh->forceFill(['password' => $password, 'must_change_password' => false])->save();
            $this->revoke($fresh);
        });
    }

    private function invalidToken(): never
    {
        throw ValidationException::withMessages(['email' => 'Tautan reset tidak berlaku atau sudah kedaluwarsa. Minta tautan baru.']);
    }

    public function operatorReset(User $actor, int $targetId, string $password): void
    {
        Validator::make(['password' => $password], ['password' => Input::passwordRules()])->validate();
        $target = User::query()->findOrFail($targetId);
        abort_unless($target->business_id, 403);
        app(BusinessTransaction::class)->run($actor, $target->business_id, function (Business $business, User $fresh) use ($targetId, $password) {
            $target = User::query()->where('business_id', $business->id)->lockForUpdate()->findOrFail($targetId);
            abort_unless(($fresh->role === 'developer' && $target->role === 'owner') || ($fresh->role === 'owner' && $target->role === 'admin'), 403);
            $target->forceFill(['password' => $password, 'must_change_password' => true])->save();
            $this->revoke($target);
            Audit::record($fresh, $business->id, 'akun.reset_password', ['user_id' => $target->id]);
        }, $actor->role === 'developer' ? 'administration' : 'business');
    }

    public function saveAdmin(User $actor, array $data, ?int $id = null): User
    {
        return app(BusinessTransaction::class)->run($actor, $actor->business_id, function (Business $business, User $fresh) use ($data, $id) {
            abort_unless($fresh->role === 'owner', 403);
            $target = $id ? User::query()->where('business_id', $business->id)->where('role', 'admin')->lockForUpdate()->findOrFail($id) : new User;
            Gate::forUser($fresh)->authorize($id ? 'update' : 'create', $id ? $target : User::class);
            $valid = $this->validateAccount($data, $id);
            $assignment = Validator::make($data, ['branch_id' => ['required', 'integer'], 'is_active' => ['required', 'boolean']])->validate();
            $branch = Branch::query()->findOrFail($assignment['branch_id']);
            if ((! $id || $branch->id !== $target->branch_id || (! $target->is_active && $assignment['is_active'])) && ! $branch->is_active) {
                throw ValidationException::withMessages(['branch_id' => 'Pilih cabang yang aktif.']);
            }
            $oldEmail = $target->email;
            $target->forceFill([...$valid, ...$assignment, 'business_id' => $business->id, 'role' => 'admin']);
            if (! $id) {
                $target->must_change_password = true;
            }
            $target->save();
            if ($id && ($oldEmail !== $target->email || ! $target->is_active)) {
                $this->revoke($target, null, $oldEmail);
            }

            return $target;
        });
    }
}

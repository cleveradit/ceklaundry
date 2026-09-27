<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Support\Facades\DB;

class BusinessTransaction
{
    public function run(User $actor, int $businessId, Closure $callback, string $mode = 'business'): mixed
    {
        return DB::transaction(function () use ($actor, $businessId, $callback, $mode) {
            $business = Business::query()->lockForUpdate()->findOrFail($businessId);
            $fresh = User::query()->findOrFail($actor->id);
            abort_unless($fresh->is_active, 403, 'Akun tidak aktif.');
            if ($mode === 'administration') {
                abort_unless($fresh->role === 'developer', 403);
                abort_if($fresh->must_change_password, 403, 'Ganti password awal terlebih dahulu.');
            } else {
                abort_unless($fresh->role !== 'developer' && $fresh->business_id === $businessId, 404);
                if ($mode === 'business') {
                    abort_if($fresh->must_change_password, 403, 'Ganti password awal terlebih dahulu.');
                    abort_unless(app(LifecycleService::class)->writable($business), 423, 'Bisnis saat ini hanya dapat dibaca.');
                    if ($fresh->role === 'admin') {
                        abort_unless(DB::table('branches')->where('id', $fresh->branch_id)->where('business_id', $businessId)->where('is_active', true)->exists(), 403, 'Cabang tidak aktif.');
                    }
                }
            }

            return app(TenantContext::class)->run($businessId, fn () => $callback($business, $fresh), $mode === 'administration' ? null : $fresh);
        }, 3);
    }
}

<?php

namespace App\Services;

use App\Models\Business;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

class DemoPurgeService
{
    public function run(): int
    {
        $ids = Business::query()->where('is_demo', true)->where('demo_expires_at', '<=', now('Asia/Jakarta'))
            ->orderBy('id')->pluck('id');
        $purged = 0;
        foreach ($ids as $id) {
            if ($this->purge((int) $id)) {
                $purged++;
            }
        }

        return $purged;
    }

    public function purge(int $businessId): bool
    {
        return DB::transaction(function () use ($businessId) {
            $business = Business::query()->lockForUpdate()->find($businessId);
            if (! $business || ! $business->is_demo || now('Asia/Jakarta')->lessThan($business->demo_expires_at)) {
                return false;
            }

            return app(TenantContext::class)->run($businessId, function () use ($businessId) {
                $userIds = DB::table('users')->where('business_id', $businessId)->pluck('id');
                $emails = DB::table('users')->where('business_id', $businessId)->pluck('email');
                DB::table('sessions')->whereIn('user_id', $userIds)->delete();
                DB::table('password_reset_tokens')->whereIn('email', $emails)->delete();
                foreach (['jobs', 'failed_jobs'] as $table) {
                    DB::table($table)->where('payload->business_id', $businessId)->delete();
                }
                foreach (['cache', 'cache_locks'] as $table) {
                    DB::table($table)->where('key', 'like', config('cache.prefix').'demo:business:'.$businessId.':%')->delete();
                }

                foreach (['notification_logs', 'status_histories', 'payments', 'loyalty_histories', 'transaction_items', 'audit_logs'] as $table) {
                    if ($table === 'transaction_items') {
                        DB::table($table)->whereIn('transaction_id', DB::table('transactions')->where('business_id', $businessId)->select('id'))->delete();
                    } else {
                        DB::table($table)->where('business_id', $businessId)->delete();
                    }
                }
                DB::table('transactions')->where('business_id', $businessId)->delete();
                DB::table('promo_branches')->whereIn('promo_id', DB::table('promos')->where('business_id', $businessId)->select('id'))->delete();
                foreach (['promos', 'loyalty_settings', 'services', 'master_services', 'customers', 'users', 'branches', 'business_settings'] as $table) {
                    DB::table($table)->where('business_id', $businessId)->delete();
                }
                DB::table('businesses')->where('id', $businessId)->delete();

                return true;
            });
        }, 3);
    }
}

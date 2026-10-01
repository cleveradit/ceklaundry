<?php

namespace App\Services;

use App\Models\Business;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ReminderScheduler
{
    public function run(): int
    {
        $ids = DB::table('transactions')->where('status', 'SIAP_DIAMBIL')->whereNotNull('waktu_siap_diambil')
            ->orderBy('id')->pluck('id');
        $reserved = 0;
        foreach ($ids as $id) {
            $parent = DB::table('transactions')->where('id', $id)->first(['business_id']);
            if (! $parent) {
                continue;
            }
            DB::transaction(function () use ($parent, $id, &$reserved) {
                $business = Business::query()->lockForUpdate()->find($parent->business_id);
                if (! $business || ! app(LifecycleService::class)->writable($business)) {
                    return;
                }
                app(TenantContext::class)->run($business->id, function () use ($business, $id, &$reserved) {
                    $parent = DB::table('transactions')->where('business_id', $business->id)->where('id', $id)->first();
                    if (! $parent) {
                        return;
                    }
                    DB::table('customers')->where('business_id', $business->id)->where('id', $parent->customer_id)->lockForUpdate()->first();
                    $tx = DB::table('transactions')->where('business_id', $business->id)->where('id', $id)->lockForUpdate()->first();
                    $setting = DB::table('business_settings')->where('business_id', $business->id)->first();
                    if ($tx->status !== 'SIAP_DIAMBIL' || ! $setting->reminder_enabled ||
                        $tx->reminder_count >= $setting->reminder_max_count ||
                        (! $business->is_demo && ! app(OutboundGuard::class)->allows($business, $tx->waktu_siap_diambil))) {
                        return;
                    }
                    $base = $tx->last_reminder_at ?: $tx->waktu_siap_diambil;
                    $days = $tx->reminder_count ? $setting->reminder_interval_days : $setting->reminder_first_days;
                    if (CarbonImmutable::parse($base, 'Asia/Jakarta')->addDays($days)->greaterThan(now('Asia/Jakarta'))) {
                        return;
                    }
                    $number = (int) $tx->reminder_count + 1;
                    $dispatcher = app(NotificationDispatcher::class);
                    $email = $dispatcher->reserve($business, $tx, 'pengingat', 'email', $number);
                    $wa = $dispatcher->reserve($business, $tx, 'pengingat', 'whatsapp', $number);
                    if ($email || $wa) {
                        DB::table('transactions')->where('id', $id)->update([
                            'reminder_count' => $number, 'last_reminder_at' => now('Asia/Jakarta'), 'updated_at' => now(),
                        ]);
                        $reserved++;
                    }
                });
            }, 3);
        }

        return $reserved;
    }
}

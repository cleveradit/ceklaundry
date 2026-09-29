<?php

namespace App\Services;

use App\Jobs\SendNotification;
use App\Models\Business;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class NotificationRecoveryService
{
    public function recover(): int
    {
        $ids = DB::table('notification_logs')->where(function ($query) {
            $query->where(fn ($pending) => $pending->where('status', 'tertunda')->where('next_attempt_at', '<=', now())
                ->where('last_enqueued_at', '<=', now()->subMinutes(5)))
                ->orWhere(fn ($processing) => $processing->where('status', 'diproses')->where('processing_started_at', '<=', now()->subMinutes(5)));
        })->orderBy('id')->limit(200)->pluck('id');
        $count = 0;
        foreach ($ids as $id) {
            $parent = DB::table('notification_logs')->where('id', $id)->first(['business_id']);
            if (! $parent) {
                continue;
            }
            DB::transaction(function () use ($parent, $id, &$count) {
                $business = Business::query()->lockForUpdate()->find($parent->business_id);
                if (! $business) {
                    return;
                }
                app(TenantContext::class)->run($business->id, function () use ($business, $id, &$count) {
                    $parent = DB::table('notification_logs')->where('business_id', $business->id)->where('id', $id)->first();
                    $tx = $parent ? DB::table('transactions')->where('business_id', $business->id)->where('id', $parent->transaction_id)->lockForUpdate()->first() : null;
                    $log = $tx ? DB::table('notification_logs')->where('id', $id)->lockForUpdate()->first() : null;
                    if (! $log) {
                        return;
                    }
                    $blocked = ! app(OutboundGuard::class)->allows($business, $log->created_at) ||
                        (! $log->is_manual && $log->tipe !== 'verifikasi_email' && ! app(OutboundGuard::class)->allows($business, $tx->waktu_siap_diambil));
                    if ($blocked) {
                        DB::table('notification_logs')->where('id', $id)->update([
                            'status' => 'perlu_pemeriksaan', 'reason_code' => config('outbound.hold') ? 'restore_hold' : 'restore_cutoff',
                            'processing_token' => null, 'processing_started_at' => null, 'next_attempt_at' => null, 'updated_at' => now(),
                        ]);

                        return;
                    }
                    if ($log->kanal === 'whatsapp') {
                        $recipient = DB::table('customers')->where('business_id', $business->id)->where('id', $tx->customer_id)->value('no_hp');
                        if ($recipient !== $log->tujuan) {
                            if ($recipient && ! $log->delivery_started_at && $log->attempt_count === 0 &&
                                in_array($log->status, ['tertunda', 'diproses'], true) && $tx->status === 'SIAP_DIAMBIL' &&
                                app(LifecycleService::class)->writable($business) && $business->wa_enabled) {
                                DB::table('notification_logs')->where('id', $id)->update([
                                    'tujuan' => $recipient, 'status' => 'tertunda', 'processing_token' => null,
                                    'processing_started_at' => null, 'next_attempt_at' => now(),
                                    'last_enqueued_at' => now(), 'updated_at' => now(),
                                ]);
                                Bus::dispatch(new SendNotification($business->id, $id));
                                $count++;
                            } else {
                                DB::table('notification_logs')->where('id', $id)->update([
                                    'status' => 'perlu_pemeriksaan', 'reason_code' => 'recipient_berubah',
                                    'processing_token' => null, 'processing_started_at' => null, 'next_attempt_at' => null, 'updated_at' => now(),
                                ]);
                            }

                            return;
                        }
                    }
                    if ($log->status === 'diproses' && $log->processing_started_at <= now()->subMinutes(5)) {
                        if ($log->delivery_started_at || $log->attempt_count > 0) {
                            DB::table('notification_logs')->where('id', $id)->update([
                                'status' => 'perlu_pemeriksaan', 'reason_code' => 'lease_sesudah_attempt',
                                'next_attempt_at' => null, 'updated_at' => now(),
                            ]);

                            return;
                        }
                        DB::table('notification_logs')->where('id', $id)->update([
                            'status' => 'tertunda', 'processing_token' => null, 'processing_started_at' => null,
                            'next_attempt_at' => now(), 'last_enqueued_at' => now(), 'updated_at' => now(),
                        ]);
                        Bus::dispatch(new SendNotification($business->id, $id));
                        $count++;
                    } elseif ($log->status === 'tertunda' && $log->next_attempt_at <= now() &&
                        $log->last_enqueued_at <= now()->subMinutes(5)) {
                        DB::table('notification_logs')->where('id', $id)->update(['last_enqueued_at' => now(), 'updated_at' => now()]);
                        Bus::dispatch(new SendNotification($business->id, $id));
                        $count++;
                    }
                });
            }, 3);
        }

        return $count;
    }
}

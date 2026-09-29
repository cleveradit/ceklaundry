<?php

namespace App\Services;

use App\Jobs\SendNotification;
use App\Models\Business;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class WaRecipientReconciler
{
    /** Called after customer identity changes, under the business root lock. */
    public function reconcile(Business $business, array $transactionIds, string $newPhone): void
    {
        if (! $transactionIds) {
            return;
        }
        $txs = DB::table('transactions')->where('business_id', $business->id)->whereIn('id', $transactionIds)
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $logs = DB::table('notification_logs')->where('business_id', $business->id)->whereIn('transaction_id', $transactionIds)
            ->where('kanal', 'whatsapp')->orderBy('id')->lockForUpdate()->get();
        foreach ($logs as $log) {
            if ($log->tujuan === $newPhone) {
                continue;
            }
            $tx = $txs[$log->transaction_id] ?? null;
            if (! $tx) {
                continue;
            }
            if (in_array($log->status, ['tertunda', 'diproses'], true) && ! $log->delivery_started_at && $log->attempt_count === 0) {
                $setting = DB::table('business_settings')->where('business_id', $business->id)->first();
                $eligible = app(LifecycleService::class)->writable($business) &&
                    $business->wa_enabled && $business->wa_provider && $business->wa_token &&
                    ($log->tipe !== 'siap_diambil' || $setting->wa_on_ready) &&
                    ($log->tipe !== 'pengingat' || ($setting->reminder_enabled && $setting->wa_on_reminder && $log->reminder_number <= $setting->reminder_max_count)) &&
                    app(OutboundGuard::class)->allows($business, $log->created_at) &&
                    app(OutboundGuard::class)->allows($business, $tx->waktu_siap_diambil) && $tx->status === 'SIAP_DIAMBIL';
                if ($eligible) {
                    DB::table('notification_logs')->where('id', $log->id)->update([
                        'tujuan' => $newPhone, 'status' => 'tertunda', 'processing_token' => null,
                        'processing_started_at' => null, 'next_attempt_at' => now(), 'last_enqueued_at' => now(), 'updated_at' => now(),
                    ]);
                    Bus::dispatch(new SendNotification($business->id, $log->id));
                } else {
                    DB::table('notification_logs')->where('id', $log->id)->update([
                        'status' => 'dilewati_kondisi', 'reason_code' => 'recipient_tidak_eligible',
                        'processing_token' => null, 'processing_started_at' => null, 'next_attempt_at' => null, 'updated_at' => now(),
                    ]);
                }
            } elseif (in_array($log->status, ['tertunda', 'diproses', 'perlu_pemeriksaan'], true) &&
                ($log->delivery_started_at || $log->attempt_count > 0)) {
                DB::table('notification_logs')->where('id', $log->id)->update([
                    'status' => 'perlu_pemeriksaan', 'reason_code' => 'recipient_berubah',
                    'processing_token' => null, 'processing_started_at' => null, 'next_attempt_at' => null, 'updated_at' => now(),
                ]);
            }
        }
    }
}

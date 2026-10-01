<?php

namespace App\Services;

use App\Jobs\SendNotification;
use App\Models\Business;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class NotificationDispatcher
{
    /** Caller holds the business root lock and uses the same database connection as the queue. */
    public function reserve(Business $business, object $tx, string $type, string $channel, ?int $number = null, ?array $manual = null): ?int
    {
        $readyAt = $tx->waktu_siap_diambil ?? null;
        $guard = app(OutboundGuard::class);
        if (! $business->is_demo && (! $guard->allows($business, now()) || (! $manual && $type !== 'verifikasi_email' && ! $guard->allows($business, $readyAt)))) {
            return null;
        }
        $settings = DB::table('business_settings')->where('business_id', $business->id)->first();
        $customer = DB::table('customers')->where('business_id', $business->id)->where('id', $tx->customer_id)->first();
        if (! $settings || ! $customer) {
            return null;
        }
        if ($channel === 'email') {
            $recipient = $type === 'verifikasi_email' ? $tx->pending_notification_email : $tx->notification_email;
            if (! $recipient) {
                return null;
            }
        } elseif ($channel === 'whatsapp') {
            if (! $business->wa_enabled || ! $business->wa_provider || ! $business->wa_token || ! $business->wa_sender_number ||
                ($type === 'siap_diambil' && ! $settings->wa_on_ready) || ($type === 'pengingat' && ! $settings->wa_on_reminder)) {
                return null;
            }
            $recipient = $customer->no_hp;
        } else {
            return null;
        }
        if ($type === 'pengingat' && ! $settings->reminder_enabled && ! $manual) {
            return null;
        }
        $keyType = $type === 'siap_diambil' ? 'ready' : ($type === 'pengingat' ? 'reminder' : 'verify');
        $key = $manual ? sprintf('tx:%d:manual:%s:%s:%s', $tx->id, $type, $channel, $manual['uuid']) :
            ($type === 'verifikasi_email' ? sprintf('tx:%d:verify:email:%d', $tx->id, $tx->email_verification_version) :
                sprintf('tx:%d:%s:%s:%d', $tx->id, $keyType, $channel, $number ?? 0));
        $existing = DB::table('notification_logs')->where('notification_key', $key)->first();
        if ($existing) {
            if ($manual && ! hash_equals((string) $existing->request_hash, $manual['hash'])) {
                abort(409, 'Kunci permintaan dipakai dengan data berbeda.');
            }

            return $existing->id;
        }
        $quotaMonth = null;
        $status = $business->is_demo ? 'ditekan_demo' : 'tertunda';
        if ($channel === 'whatsapp' && ! $business->is_demo) {
            if (app(WaQuotaService::class)->available($business->id)) {
                $quotaMonth = app(WaQuotaService::class)->month();
            } else {
                $status = 'dilewati_batas';
            }
        }
        $at = now('Asia/Jakarta');
        $id = DB::table('notification_logs')->insertGetId([
            'business_id' => $business->id, 'transaction_id' => $tx->id,
            'kanal' => $channel, 'tipe' => $type, 'requested_by' => $manual['user_id'] ?? null,
            'is_manual' => (bool) $manual, 'reminder_number' => $manual ? null : ($type === 'siap_diambil' ? 0 : $number),
            'verification_version' => $type === 'verifikasi_email' ? $tx->email_verification_version : null,
            'notification_key' => $key, 'request_hash' => $manual['hash'] ?? null,
            'tujuan' => $recipient, 'status' => $status, 'attempt_count' => 0,
            'next_attempt_at' => $status === 'tertunda' ? $at : null,
            'last_enqueued_at' => $status === 'tertunda' ? $at : null,
            'wa_quota_month' => $quotaMonth, 'created_at' => $at, 'updated_at' => $at,
        ]);
        if ($status === 'tertunda') {
            Bus::dispatch(new SendNotification($business->id, $id));
        }

        return $id;
    }
}

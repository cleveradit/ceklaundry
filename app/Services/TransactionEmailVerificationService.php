<?php

namespace App\Services;

use App\Models\Business;
use App\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionEmailVerificationService
{
    public function request(string $code, string $email, Request $request): bool
    {
        $code = app(PublicReceiptService::class)->normalize($code);
        $email = Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:150']])->validate()['email'];
        $this->limit($code, $email, $request);
        $parent = DB::table('transactions')->where('kode_resi', $code)->first();
        abort_unless($parent, 404);
        $demo = false;
        DB::transaction(function () use ($parent, $code, $email, &$demo) {
            $business = Business::query()->lockForUpdate()->findOrFail($parent->business_id);
            $demo = $business->is_demo;
            abort_unless(app(LifecycleService::class)->writable($business), 423, 'Bisnis saat ini hanya dapat dibaca.');
            abort_unless($business->is_demo || app(OutboundGuard::class)->allows($business, now()), 503, 'Email sementara tidak tersedia.');
            app(TenantContext::class)->run($business->id, function () use ($business, $parent, $code, $email) {
                $visible = DB::table('transactions')->where('business_id', $business->id)->where('id', $parent->id)->first();
                DB::table('customers')->where('business_id', $business->id)->where('id', $visible->customer_id)->lockForUpdate()->first();
                $tx = DB::table('transactions')->where('id', $parent->id)->lockForUpdate()->first();
                abort_unless($tx && $tx->kode_resi === $code, 404);
                abort_unless(in_array($tx->status, ['DITERIMA', 'DIPROSES'], true), 422, 'Email hanya dapat ditambahkan sebelum cucian siap.');
                DB::table('notification_logs')->where('business_id', $business->id)->where('transaction_id', $tx->id)
                    ->where('tipe', 'verifikasi_email')->whereIn('status', ['tertunda', 'diproses'])->whereNull('delivery_started_at')
                    ->update(['status' => 'dilewati_kondisi', 'reason_code' => 'verifikasi_diganti', 'processing_token' => null,
                        'processing_started_at' => null, 'next_attempt_at' => null, 'updated_at' => now()]);
                DB::table('transactions')->where('id', $tx->id)->update([
                    'pending_notification_email' => $email, 'email_verification_version' => $tx->email_verification_version + 1,
                    'email_verification_expires_at' => now()->addHours(24), 'version' => $tx->version + 1, 'updated_at' => now(),
                ]);
                app(NotificationDispatcher::class)->reserve($business, DB::table('transactions')->where('id', $tx->id)->first(), 'verifikasi_email', 'email');
            });
        }, 3);

        return $demo;
    }

    public function confirm(string $code, int $version): void
    {
        $code = app(PublicReceiptService::class)->normalize($code);
        $parent = DB::table('transactions')->where('kode_resi', $code)->first();
        abort_unless($parent, 404);
        DB::transaction(function () use ($parent, $code, $version) {
            $business = Business::query()->lockForUpdate()->findOrFail($parent->business_id);
            abort_if($business->is_demo, 403, 'Konfirmasi email tidak tersedia pada demo.');
            abort_unless(app(LifecycleService::class)->writable($business), 423, 'Bisnis saat ini hanya dapat dibaca.');
            app(TenantContext::class)->run($business->id, function () use ($business, $parent, $code, $version) {
                $visible = DB::table('transactions')->where('business_id', $business->id)->where('id', $parent->id)->first();
                DB::table('customers')->where('business_id', $business->id)->where('id', $visible->customer_id)->lockForUpdate()->first();
                $tx = DB::table('transactions')->where('id', $parent->id)->lockForUpdate()->first();
                abort_unless($tx && $tx->kode_resi === $code, 404);
                abort_unless(in_array($tx->status, ['DITERIMA', 'DIPROSES'], true) && $tx->pending_notification_email &&
                    (int) $tx->email_verification_version === $version && now()->lessThan($tx->email_verification_expires_at),
                    422, 'Tautan konfirmasi tidak berlaku.');
                DB::table('transactions')->where('id', $tx->id)->update([
                    'notification_email' => $tx->pending_notification_email, 'pending_notification_email' => null,
                    'email_verification_expires_at' => null, 'email_verification_version' => $version + 1,
                    'version' => $tx->version + 1, 'updated_at' => now(),
                ]);
            });
        }, 3);
    }

    private function limit(string $code, string $email, Request $request): void
    {
        $secret = config('app.key');
        $ip = hash_hmac('sha256', (string) $request->ip(), $secret);
        $keys = [
            ['email:receipt:'.hash_hmac('sha256', $code, $secret).':'.now('Asia/Jakarta')->format('YmdH'), 3, 3700],
            ['email:ip:'.$ip.':'.now('Asia/Jakarta')->format('Ymd'), 10, 90000],
            ['email:recipient:'.hash_hmac('sha256', mb_strtolower($email), $secret).':'.now('Asia/Jakarta')->format('YmdH'), 5, 3700],
        ];
        $lock = Cache::lock('receipt-email-limiter', 5);
        $lock->block(5, function () use ($keys) {
            foreach ($keys as [$key, $max]) {
                abort_if((int) Cache::get($key, 0) >= $max, 429, 'Terlalu banyak permintaan. Coba lagi nanti.');
            }
            foreach ($keys as [$key, , $ttl]) {
                Cache::add($key, 0, $ttl);
                Cache::increment($key);
            }
        });
    }
}

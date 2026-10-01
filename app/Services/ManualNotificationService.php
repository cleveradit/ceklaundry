<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManualNotificationService
{
    public function email(User $actor, int $id, array $input): int
    {
        $data = Validator::make($input, ['request_key' => ['required', 'uuid'], 'confirm_unknown' => ['sometimes', 'boolean']])->validate();

        return app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, User $fresh) use ($id, $data) {
            abort_unless(in_array($fresh->role, ['owner', 'admin'], true), 403);
            $visible = app(OperationalAccess::class)->transaction($fresh, $id);
            $customer = DB::table('customers')->where('business_id', $business->id)->where('id', $visible->customer_id)->lockForUpdate()->first();
            $tx = DB::table('transactions')->where('business_id', $business->id)->where('id', $id)->lockForUpdate()->first();
            abort_unless($tx->status === 'SIAP_DIAMBIL', 422, 'Pengingat hanya tersedia ketika cucian siap diambil.');
            abort_unless($tx->notification_email, 422, 'Email transaksi belum tersedia.');
            abort_unless($business->is_demo || app(OutboundGuard::class)->allows($business, now()), 503, 'Pengiriman sementara tidak tersedia.');
            $key = sprintf('tx:%d:manual:pengingat:email:%s', $id, $data['request_key']);
            $hash = hash('sha256', $tx->notification_email.'|'.$id.'|pengingat');
            $existing = DB::table('notification_logs')->where('notification_key', $key)->first();
            if ($existing) {
                abort_unless(hash_equals((string) $existing->request_hash, $hash), 409, 'Kunci permintaan dipakai dengan data berbeda.');

                return $existing->id;
            }
            $recent = DB::table('notification_logs')->where('business_id', $business->id)->where('transaction_id', $id)
                ->where('kanal', 'email')->where('tipe', 'pengingat')->where('is_manual', true)
                ->where('created_at', '>', now()->subMinutes(10))->exists();
            abort_if($recent, 429, 'Tunggu 10 menit sebelum meminta email baru.');
            $today = DB::table('notification_logs')->where('business_id', $business->id)->where('requested_by', $fresh->id)
                ->where('kanal', 'email')->where('is_manual', true)
                ->whereDate('created_at', now('Asia/Jakarta')->toDateString())->count();
            abort_if($today >= 20, 429, 'Batas 20 email manual hari ini tercapai.');
            $unknown = DB::table('notification_logs')->where('business_id', $business->id)->where('transaction_id', $id)
                ->where('status', 'perlu_pemeriksaan')->exists();
            abort_if($unknown && ! ($data['confirm_unknown'] ?? false), 409, 'Hasil email sebelumnya belum diketahui. Konfirmasi risiko sebelum mengirim lagi.');

            return app(NotificationDispatcher::class)->reserve($business, $tx, 'pengingat', 'email', null,
                ['uuid' => $data['request_key'], 'hash' => $hash, 'user_id' => $fresh->id]);
        });
    }

    public function whatsappLink(User $actor, int $id, string $type, string $uuid, bool $confirmUnknown = false): string
    {
        abort_unless(in_array($type, ['resi', 'pengingat'], true), 422);
        Validator::make(['key' => $uuid], ['key' => ['required', 'uuid']])->validate();

        return app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, User $fresh) use ($id, $type, $uuid, $confirmUnknown) {
            $visible = app(OperationalAccess::class)->transaction($fresh, $id);
            $customer = DB::table('customers')->where('business_id', $business->id)->where('id', $visible->customer_id)->lockForUpdate()->first();
            $tx = DB::table('transactions')->where('business_id', $business->id)->where('id', $id)->lockForUpdate()->first();
            abort_if($type === 'pengingat' && $tx->status !== 'SIAP_DIAMBIL', 422, 'Pengingat hanya tersedia ketika cucian siap diambil.');
            abort_unless($business->is_demo || app(OutboundGuard::class)->allows($business, now()), 503, 'Tautan sementara tidak tersedia.');
            $key = sprintf('tx:%d:manual:%s:whatsapp_manual:%s', $id, $type, $uuid);
            $hash = hash('sha256', $type.'|'.$customer->no_hp.'|'.$id);
            $existing = DB::table('notification_logs')->where('notification_key', $key)->first();
            abort_if($existing && ! hash_equals((string) $existing->request_hash, $hash), 409, 'Kunci permintaan dipakai dengan data berbeda.');
            if (! $existing) {
                $unknown = DB::table('notification_logs')->where('business_id', $business->id)->where('transaction_id', $id)
                    ->where('status', 'perlu_pemeriksaan')->exists();
                abort_if($unknown && ! $confirmUnknown, 409, 'Hasil kiriman sebelumnya belum diketahui. Konfirmasi risiko sebelum menghubungi lagi.');
                DB::table('notification_logs')->insert([
                    'business_id' => $business->id, 'transaction_id' => $id, 'kanal' => 'whatsapp_manual',
                    'tipe' => $type, 'requested_by' => $fresh->id, 'is_manual' => true,
                    'notification_key' => $key, 'request_hash' => $hash, 'tujuan' => $customer->no_hp,
                    'status' => $business->is_demo ? 'ditekan_demo' : 'dibuka_manual', 'attempt_count' => 0, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $paid = (int) DB::table('payments')->where('transaction_id', $id)->sum('jumlah');

            $links = app(ManualReceiptLinkService::class);

            return $business->is_demo
                ? 'demo-preview:'.$links->message($tx, $paid, $type)
                : $links->link($tx, $customer->no_hp, $paid, $type);
        });
    }
}

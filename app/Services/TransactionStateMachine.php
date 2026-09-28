<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionStateMachine
{
    private const NEXT = ['DITERIMA' => 'DIPROSES', 'DIPROSES' => 'SIAP_DIAMBIL', 'SIAP_DIAMBIL' => 'SUDAH_DIAMBIL'];

    public function move(User $actor, int $id, string $target, int $expectedVersion, ?string $reason = null): void
    {
        if ($target === 'DIBATALKAN') {
            app(CancellationService::class)->cancel($actor, $id, $expectedVersion, $reason);

            return;
        }
        app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, User $fresh) use ($id, $target, $expectedVersion) {
            $visible = app(OperationalAccess::class)->transaction($fresh, $id);
            app(OperationalAccess::class)->branch($fresh, $visible->branch_id);
            DB::table('customers')->where('business_id', $business->id)->where('id', $visible->customer_id)->lockForUpdate()->first();
            $tx = DB::table('transactions')->where('business_id', $business->id)->where('id', $id)->lockForUpdate()->first();
            if ($tx->status === $target) {
                return;
            }
            abort_unless($expectedVersion === (int) $tx->version, 409, 'Status berubah. Muat ulang.');
            abort_unless((self::NEXT[$tx->status] ?? null) === $target, 422, 'Perubahan status tidak diizinkan.');
            if ($target === 'SUDAH_DIAMBIL') {
                $paid = (int) DB::table('payments')->where('transaction_id', $id)->sum('jumlah');
                abort_unless($paid === (int) $tx->total_akhir && $tx->status_bayar === 'LUNAS', 422, 'Lunasi sisa tagihan sebelum penyerahan.');
            }
            $at = now('Asia/Jakarta');
            $changes = ['status' => $target, 'version' => $tx->version + 1, 'updated_at' => $at];
            if ($target === 'SIAP_DIAMBIL') {
                $changes['waktu_siap_diambil'] = $at;
            }
            if ($target === 'SUDAH_DIAMBIL') {
                $changes['waktu_diambil'] = $at;
                app(PendingNotificationInvalidator::class)->forTransaction($business->id, $id);
            }
            DB::table('transactions')->where('id', $id)->update($changes);
            DB::table('status_histories')->insert(['business_id' => $business->id, 'transaction_id' => $id, 'status' => $target, 'user_id' => $fresh->id, 'created_at' => $at]);
        });
    }
}

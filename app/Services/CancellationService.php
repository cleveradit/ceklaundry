<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancellationService
{
    public function cancel(User $actor, int $id, int $expectedVersion, ?string $reason): void
    {
        app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, User $fresh) use ($id, $expectedVersion, $reason) {
            $visible = app(OperationalAccess::class)->transaction($fresh, $id);
            app(OperationalAccess::class)->branch($fresh, $visible->branch_id);
            DB::table('customers')->where('business_id', $business->id)->where('id', $visible->customer_id)->lockForUpdate()->first();
            $tx = DB::table('transactions')->where('business_id', $business->id)->where('id', $id)->lockForUpdate()->first();
            if ($tx->status === 'DIBATALKAN') {
                return;
            }
            abort_unless($expectedVersion === (int) $tx->version, 409, 'Transaksi berubah. Muat ulang.');
            abort_unless(in_array($tx->status, ['DITERIMA', 'DIPROSES', 'SIAP_DIAMBIL'], true), 422, 'Transaksi tidak dapat dibatalkan.');
            $reason = trim((string) $reason);
            if ($reason === '' || mb_strlen($reason) > 5000) {
                throw ValidationException::withMessages(['alasan_pembatalan' => 'Alasan pembatalan wajib diisi (maksimal 5.000 karakter).']);
            }
            $at = now('Asia/Jakarta');
            app(LoyaltyLedgerService::class)->compensate((int) $business->id, (int) $tx->customer_id, $id);
            app(PendingNotificationInvalidator::class)->forTransaction($business->id, $id);
            DB::table('transactions')->where('id', $id)->update([
                'status' => 'DIBATALKAN', 'alasan_pembatalan' => $reason,
                'pending_notification_email' => null, 'email_verification_expires_at' => null,
                'version' => $tx->version + 1, 'updated_at' => $at,
            ]);
            DB::table('status_histories')->insert(['business_id' => $business->id, 'transaction_id' => $id, 'status' => 'DIBATALKAN', 'user_id' => $fresh->id, 'created_at' => $at]);
            Audit::record($fresh, $business->id, 'transaksi.batal', ['transaction_id' => $id, 'alasan' => $reason, 'status_sebelum' => $tx->status]);
        });
    }
}

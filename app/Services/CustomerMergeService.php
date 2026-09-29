<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerMergeService
{
    public function merge(User $actor, int $sourceId, int $targetId): void
    {
        if ($sourceId === $targetId) {
            throw ValidationException::withMessages(['target_id' => 'Pilih pelanggan tujuan yang berbeda.']);
        }
        app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, User $fresh) use ($sourceId, $targetId) {
            abort_unless(in_array($fresh->role, ['owner', 'admin'], true), 403);
            $customers = DB::table('customers')->where('business_id', $business->id)
                ->whereIn('id', [$sourceId, $targetId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            abort_unless($customers->count() === 2, 404);
            $source = $customers[$sourceId];
            $target = $customers[$targetId];
            $txs = DB::table('transactions')->where('business_id', $business->id)->whereIn('customer_id', [$sourceId, $targetId])->orderBy('id')->lockForUpdate()->get();
            if ($fresh->role === 'admin' && $txs->contains(fn ($tx) => (int) $tx->branch_id !== (int) $fresh->branch_id)) {
                abort(403, 'Penggabungan ini memerlukan owner.');
            }
            $ids = $txs->where('customer_id', $sourceId)->pluck('id')->all();
            if ($ids) {
                DB::table('notification_logs')->where('business_id', $business->id)->whereIn('transaction_id', $ids)->orderBy('id')->lockForUpdate()->get();
                DB::table('notification_logs')->where('business_id', $business->id)->whereIn('transaction_id', $ids)
                    ->where('tipe', 'verifikasi_email')->whereIn('status', ['tertunda', 'diproses'])->whereNull('delivery_started_at')
                    ->update(['status' => 'dilewati_kondisi', 'reason_code' => 'pelanggan_digabung', 'processing_token' => null,
                        'processing_started_at' => null, 'next_attempt_at' => null, 'updated_at' => now()]);
                DB::table('transactions')->whereIn('id', $ids)->update([
                    'customer_id' => $targetId, 'pending_notification_email' => null,
                    'email_verification_expires_at' => null, 'version' => DB::raw('version + 1'), 'updated_at' => now(),
                ]);
                app(WaRecipientReconciler::class)->reconcile($business, $ids, $target->no_hp);
                DB::table('loyalty_histories')->where('business_id', $business->id)->where('customer_id', $sourceId)->update(['customer_id' => $targetId]);
            }
            $balance = (int) DB::table('loyalty_histories')->where('business_id', $business->id)->where('customer_id', $targetId)->sum('jumlah');
            DB::table('customers')->where('id', $targetId)->update(['stamp_count' => $balance, 'updated_at' => now()]);
            Audit::record($fresh, $business->id, 'pelanggan.gabung', [
                'source_id' => $sourceId, 'source_nama' => $source->nama, 'source_no_hp' => $source->no_hp,
                'target_id' => $targetId, 'target_nama' => $target->nama, 'target_no_hp' => $target->no_hp,
                'saldo_sebelum' => [$source->stamp_count, $target->stamp_count], 'saldo_sesudah' => $balance,
            ]);
            DB::table('customers')->where('id', $sourceId)->delete();
        });
    }
}

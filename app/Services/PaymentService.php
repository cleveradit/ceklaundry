<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function store(User $actor, int $transactionId, array $data): object
    {
        return app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, User $fresh) use ($transactionId, $data) {
            $access = app(OperationalAccess::class);
            $visible = $access->transaction($fresh, $transactionId);
            $access->branch($fresh, (int) $visible->branch_id);
            DB::table('customers')->where('business_id', $business->id)->where('id', $visible->customer_id)->lockForUpdate()->first();
            $transaction = DB::table('transactions')->where('business_id', $business->id)->where('id', $transactionId)->lockForUpdate()->first();

            return $this->insertLocked($fresh, $transaction, $data, app(LifecycleService::class)->writable($business));
        }, 'security');
    }

    public function insertLocked(User $actor, object $transaction, array $data, bool $allowNew = true): object
    {
        $valid = Validator::make($data, [
            'request_key' => ['required', 'uuid'], 'jumlah' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'metode' => ['required', 'in:tunai,transfer'],
        ])->validate();
        if (array_intersect(['waktu', 'recorded_by', 'created_at', 'business_id', 'transaction_id'], array_keys($data))) {
            throw ValidationException::withMessages(['jumlah' => 'Waktu dan pencatat ditetapkan oleh server.']);
        }
        $hash = hash('sha256', json_encode([$transaction->id, (int) $valid['jumlah'], $valid['metode']], JSON_THROW_ON_ERROR));
        $existing = DB::table('payments')->where('business_id', $transaction->business_id)->where('request_key', $valid['request_key'])->first();
        if ($existing) {
            abort_unless($existing->request_hash === $hash && (int) $existing->transaction_id === (int) $transaction->id, 409, 'Kunci pembayaran telah dipakai dengan data berbeda.');

            return $existing;
        }
        abort_unless($allowNew, 423, 'Bisnis saat ini hanya dapat dibaca.');
        abort_unless(in_array($transaction->status, ['DITERIMA', 'DIPROSES', 'SIAP_DIAMBIL'], true), 422, 'Transaksi tidak dapat menerima pembayaran.');
        $paid = (int) DB::table('payments')->where('business_id', $transaction->business_id)->where('transaction_id', $transaction->id)->sum('jumlah');
        $remaining = (int) $transaction->total_akhir - $paid;
        $amount = (int) $valid['jumlah'];
        if ($remaining < 1 || $amount > $remaining) {
            throw ValidationException::withMessages(['jumlah' => 'Pembayaran melebihi sisa tagihan atau transaksi sudah lunas.']);
        }
        $dpEnabled = (bool) DB::table('business_settings')->where('business_id', $transaction->business_id)->value('dp_enabled');
        if ($paid === 0 && $amount < $remaining && ! $dpEnabled) {
            throw ValidationException::withMessages(['jumlah' => 'DP belum diaktifkan oleh owner.']);
        }
        $at = now('Asia/Jakarta');
        $id = DB::table('payments')->insertGetId([
            'business_id' => $transaction->business_id, 'transaction_id' => $transaction->id,
            'request_key' => $valid['request_key'], 'request_hash' => $hash,
            'jumlah' => $amount, 'metode' => $valid['metode'], 'waktu' => $at,
            'recorded_by' => $actor->id, 'created_at' => $at,
        ]);
        DB::table('transactions')->where('id', $transaction->id)->update([
            'status_bayar' => $paid + $amount === (int) $transaction->total_akhir ? 'LUNAS' : 'DP',
            'version' => $transaction->version + 1, 'updated_at' => $at,
        ]);
        if ($paid + $amount === (int) $transaction->total_akhir && $transaction->status_bayar !== 'LUNAS') {
            app(LoyaltyLedgerService::class)->award((int) $transaction->business_id, (int) $transaction->customer_id, (int) $transaction->id);
        }

        return DB::table('payments')->find($id);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoyaltyLedgerService
{
    public function balance(int $businessId, int $customerId): int
    {
        return (int) DB::table('loyalty_histories')->where('business_id', $businessId)
            ->where('customer_id', $customerId)->sum('jumlah');
    }

    public function award(int $businessId, int $customerId, int $transactionId): void
    {
        $active = DB::table('loyalty_settings')->where('business_id', $businessId)->value('is_active');
        if (! $active || DB::table('loyalty_histories')->where('transaction_id', $transactionId)
            ->whereIn('jenis', ['perolehan', 'penukaran'])->exists()) {
            return;
        }
        $this->insert($businessId, $customerId, $transactionId, 'perolehan', 1);
    }

    public function redeem(int $businessId, int $customerId, int $transactionId, int $cost): void
    {
        if ($cost < 1 || $this->balance($businessId, $customerId) < $cost) {
            throw ValidationException::withMessages(['reward_item_index' => 'Stempel tidak cukup untuk hadiah ini.']);
        }
        if (DB::table('loyalty_histories')->where('transaction_id', $transactionId)
            ->whereIn('jenis', ['penukaran', 'perolehan'])->exists()) {
            throw ValidationException::withMessages(['reward_item_index' => 'Hadiah transaksi sudah digunakan.']);
        }
        $this->insert($businessId, $customerId, $transactionId, 'penukaran', -$cost);
    }

    public function compensate(int $businessId, int $customerId, int $transactionId): void
    {
        foreach (['penukaran' => 'pengembalian_penukaran', 'perolehan' => 'pencabutan_perolehan'] as $origin => $compensation) {
            $row = DB::table('loyalty_histories')->where('business_id', $businessId)
                ->where('transaction_id', $transactionId)->where('jenis', $origin)->first();
            if ($row && ! DB::table('loyalty_histories')->where('transaction_id', $transactionId)
                ->where('jenis', $compensation)->exists()) {
                $this->insert($businessId, $customerId, $transactionId, $compensation, -(int) $row->jumlah);
            }
        }
    }

    private function insert(int $businessId, int $customerId, int $transactionId, string $type, int $amount): void
    {
        DB::table('loyalty_histories')->insert([
            'business_id' => $businessId, 'customer_id' => $customerId,
            'transaction_id' => $transactionId, 'jenis' => $type,
            'jumlah' => $amount, 'created_at' => now('Asia/Jakarta'),
        ]);
        DB::table('customers')->where('business_id', $businessId)->where('id', $customerId)
            ->increment('stamp_count', $amount);
    }
}

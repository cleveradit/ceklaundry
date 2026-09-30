<?php

namespace App\Services;

use App\Support\ServiceName;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function quote(int $businessId, int $branchId, array $items, ?int $customerId = null, ?int $rewardItemIndex = null, ?int $promoId = null): array
    {
        if (count($items) < 1 || count($items) > 100) {
            throw ValidationException::withMessages(['items' => 'Pilih 1 sampai 100 baris layanan.']);
        }
        $rows = [];
        $total = 0;
        foreach ($items as $index => $input) {
            if (! is_array($input) || ! filter_var($input['service_id'] ?? null, FILTER_VALIDATE_INT)) {
                throw ValidationException::withMessages(["items.$index.service_id" => 'Pilih layanan yang valid.']);
            }
            $service = DB::table('services')->where('business_id', $businessId)->where('branch_id', $branchId)
                ->where('id', $input['service_id'])->where('is_active', true)->first();
            if (! $service) {
                throw ValidationException::withMessages(["items.$index.service_id" => 'Layanan cabang tidak tersedia.']);
            }
            $quantity = null;
            $units = null;
            if ($service->satuan === 'kg') {
                $raw = (string) ($input['berat_kg'] ?? '');
                if (! preg_match('/^\d{1,4}(?:\.\d)?$/D', $raw)) {
                    throw ValidationException::withMessages(["items.$index.berat_kg" => 'Berat harus satu angka desimal.']);
                }
                [$whole, $fraction] = array_pad(explode('.', $raw, 2), 2, '0');
                $quantity = (int) $whole * 10 + (int) $fraction;
                if ($quantity < 1 || $quantity > 99999) {
                    throw ValidationException::withMessages(["items.$index.berat_kg" => 'Berat di luar batas.']);
                }
                if ($service->berat_minimum === null) {
                    $minimum = 0;
                } else {
                    [$minWhole, $minFraction] = array_pad(explode('.', (string) $service->berat_minimum, 2), 2, '0');
                    $minimum = (int) $minWhole * 10 + (int) $minFraction;
                }
                $subtotal = intdiv((int) $service->harga * max($quantity, $minimum) + 5, 10);
            } else {
                $units = filter_var($input['jumlah_unit'] ?? null, FILTER_VALIDATE_INT);
                if ($units === false || $units < 1 || $units > 65535) {
                    throw ValidationException::withMessages(["items.$index.jumlah_unit" => 'Jumlah unit di luar batas.']);
                }
                $subtotal = (int) $service->harga * $units;
            }
            $clothes = $input['perkiraan_jumlah_baju'] ?? null;
            if ($clothes !== null && $clothes !== '' && (! ctype_digit((string) $clothes) || (int) $clothes > 65535)) {
                throw ValidationException::withMessages(["items.$index.perkiraan_jumlah_baju" => 'Perkiraan jumlah baju di luar batas.']);
            }
            $total += $subtotal;
            if ($subtotal > 4294967295 || $total > 4294967295) {
                throw ValidationException::withMessages(['items' => 'Total harga melebihi batas.']);
            }
            $rows[] = [
                'service_id' => $service->id, 'nama_layanan_snapshot' => $service->nama,
                'satuan_snapshot' => $service->satuan, 'harga_snapshot' => (int) $service->harga,
                'berat_minimum_snapshot' => $service->berat_minimum, 'durasi_jam_snapshot' => (int) $service->durasi_jam,
                'berat_kg' => $quantity === null ? null : number_format($quantity / 10, 1, '.', ''),
                'jumlah_unit' => $units, 'perkiraan_jumlah_baju' => $clothes === '' ? null : $clothes,
                'is_stamp_reward' => false, 'subtotal' => $subtotal,
            ];
        }
        $rewardCost = null;
        $rewardMax = null;
        $stampDiscount = 0;
        $balance = null;
        if ($rewardItemIndex !== null) {
            if ($customerId === null || ! array_key_exists($rewardItemIndex, $rows)) {
                throw ValidationException::withMessages(['reward_item_index' => 'Pilih pelanggan lama dan satu baris hadiah yang sah.']);
            }
            $customer = DB::table('customers')->where('business_id', $businessId)->where('id', $customerId)->first();
            abort_unless($customer, 404);
            $setting = DB::table('loyalty_settings')->where('business_id', $businessId)->where('is_active', true)->first();
            if (! $setting || ! $setting->master_service_id || ! $setting->berat_maks_gratis) {
                throw ValidationException::withMessages(['reward_item_index' => 'Program stempel tidak aktif.']);
            }
            $master = DB::table('master_services')->where('business_id', $businessId)
                ->where('id', $setting->master_service_id)->where('is_active', true)->where('satuan', 'kg')->first();
            $row = $rows[$rewardItemIndex];
            if (! $master || $row['satuan_snapshot'] !== 'kg' ||
                mb_strtolower(ServiceName::normalize($master->nama)) !== mb_strtolower(ServiceName::normalize($row['nama_layanan_snapshot']))) {
                throw ValidationException::withMessages(['reward_item_index' => 'Layanan hadiah belum tersedia di cabang ini.']);
            }
            $balance = app(LoyaltyLedgerService::class)->balance($businessId, $customerId);
            $rewardCost = (int) $setting->stempel_dibutuhkan;
            if ($balance < $rewardCost) {
                throw ValidationException::withMessages(['reward_item_index' => 'Stempel pelanggan belum cukup.']);
            }
            $rewardMax = $setting->berat_maks_gratis;
            $weight = $this->tenths((string) $row['berat_kg']);
            $freeWeight = min($weight, $this->tenths((string) $rewardMax));
            $stampDiscount = min($row['subtotal'], intdiv($row['harga_snapshot'] * $freeWeight + 5, 10));
            if ($stampDiscount === 0) {
                throw ValidationException::withMessages(['reward_item_index' => 'Potongan hadiah harus lebih dari nol.']);
            }
            $rows[$rewardItemIndex]['is_stamp_reward'] = true;
        }
        $eligibleBase = $total - $stampDiscount;
        $promo = null;
        $promoDiscount = 0;
        if ($promoId !== null) {
            $promo = DB::table('promos')->where('business_id', $businessId)->where('id', $promoId)
                ->first();
            abort_unless($promo, 404);
            $today = now('Asia/Jakarta')->toDateString();
            if (! $promo->is_active || $promo->mulai > $today || $promo->selesai < $today ||
                (! $promo->semua_cabang && ! DB::table('promo_branches')->where('promo_id', $promoId)->where('branch_id', $branchId)->exists()) ||
                $eligibleBase < (int) ($promo->minimal_total ?? 0)) {
                throw ValidationException::withMessages(['promo_id' => 'Promo tidak berlaku untuk cabang, tanggal atau nilai transaksi ini.']);
            }
            $promoDiscount = $promo->tipe === 'persen'
                ? min($eligibleBase, intdiv((int) $promo->nilai * $eligibleBase + 50, 100))
                : min($eligibleBase, (int) $promo->nilai);
        }
        $dpEnabled = (bool) DB::table('business_settings')->where('business_id', $businessId)->value('dp_enabled');

        return [
            'items' => $rows, 'subtotal' => $total, 'potongan_stempel' => $stampDiscount,
            'potongan_promo' => $promoDiscount, 'promo_eligible_base' => $eligibleBase,
            'total_akhir' => $eligibleBase - $promoDiscount, 'dp_enabled' => $dpEnabled,
            'reward_item_index' => $rewardItemIndex, 'reward_cost' => $rewardCost,
            'stamp_reward_max_kg_snapshot' => $rewardMax,
            'promo_id' => $promo?->id, 'promo_nama_snapshot' => $promo?->nama,
            'promo_tipe_snapshot' => $promo?->tipe, 'promo_nilai_snapshot' => $promo?->nilai,
            'fingerprint' => hash('sha256', json_encode([$rows, $total, $stampDiscount, $rewardCost, $rewardMax,
                $balance, $promo?->id, $promo?->nama, $promo?->tipe, $promo?->nilai,
                $promo?->minimal_total, $promo?->mulai, $promo?->selesai, $promo?->semua_cabang,
                $promoDiscount, $dpEnabled], JSON_THROW_ON_ERROR)),
        ];
    }

    private function tenths(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');

        return (int) $whole * 10 + (int) $fraction;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PricingService
{
    public function quote(int $businessId, int $branchId, array $items): array
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
        $dpEnabled = (bool) DB::table('business_settings')->where('business_id', $businessId)->value('dp_enabled');

        return [
            'items' => $rows, 'subtotal' => $total, 'potongan_stempel' => 0, 'potongan_promo' => 0,
            'total_akhir' => $total, 'dp_enabled' => $dpEnabled,
            'fingerprint' => hash('sha256', json_encode([$rows, $total, $dpEnabled], JSON_THROW_ON_ERROR)),
        ];
    }
}

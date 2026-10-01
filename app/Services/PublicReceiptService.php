<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\DB;

class PublicReceiptService
{
    public function normalize(string $code): string
    {
        $code = strtoupper(trim($code));
        abort_unless((bool) preg_match('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{6}$/D', $code), 404, 'Kode resi tidak ditemukan, periksa kembali resi Anda');

        return $code;
    }

    public function resolve(string $code): array
    {
        $code = $this->normalize($code);
        $tx = DB::table('transactions')->where('kode_resi', $code)->first();
        abort_unless($tx, 404, 'Kode resi tidak ditemukan, periksa kembali resi Anda');
        $business = Business::query()->findOrFail($tx->business_id);
        abort_if($business->is_demo && ! app(LifecycleService::class)->publicAllowed($business), 410, 'Demo berakhir.');
        abort_unless(app(LifecycleService::class)->publicAllowed($business), 404, 'Kode resi tidak ditemukan, periksa kembali resi Anda');
        $branch = DB::table('branches')->where('business_id', $tx->business_id)->where('id', $tx->branch_id)->first();
        $customer = DB::table('customers')->where('business_id', $tx->business_id)->where('id', $tx->customer_id)->first();
        $items = DB::table('transaction_items')->where('transaction_id', $tx->id)->orderBy('id')
            ->get(['nama_layanan_snapshot', 'satuan_snapshot', 'harga_snapshot', 'berat_kg', 'jumlah_unit', 'subtotal', 'is_stamp_reward']);
        $history = DB::table('status_histories')->where('business_id', $tx->business_id)->where('transaction_id', $tx->id)
            ->orderBy('created_at')->get(['status', 'created_at']);
        $paid = (int) DB::table('payments')->where('business_id', $tx->business_id)->where('transaction_id', $tx->id)->sum('jumlah');
        $name = (string) ($customer->nama ?? 'Pelanggan');
        $phone = (string) ($customer->no_hp ?? '');
        $loyalty = DB::table('loyalty_settings')->where('business_id', $tx->business_id)->first();
        $stamps = $loyalty && $loyalty->is_active && $customer
            ? ['balance' => app(LoyaltyLedgerService::class)->balance((int) $tx->business_id, (int) $customer->id),
                'target' => (int) $loyalty->stempel_dibutuhkan] : null;

        return [
            'kode_resi' => $tx->kode_resi, 'status' => $tx->status, 'status_bayar' => $tx->status_bayar,
            'nama_pelanggan' => mb_substr($name, 0, mb_strlen($name) <= 3 ? 1 : 3).'***',
            'no_hp_pelanggan' => substr($phone, 0, 4).'***'.substr($phone, -3),
            'cabang' => ['nama' => $branch->nama, 'alamat' => $branch->alamat, 'telepon' => $branch->telepon],
            'waktu_masuk' => $tx->waktu_masuk, 'estimasi_selesai' => $tx->estimasi_selesai,
            'catatan_kondisi' => $tx->catatan_kondisi, 'items' => $items, 'timeline' => $history,
            'subtotal' => (int) $tx->subtotal, 'potongan_stempel' => (int) $tx->potongan_stempel,
            'potongan_promo' => (int) $tx->potongan_promo, 'total_akhir' => (int) $tx->total_akhir,
            'promo_nama_snapshot' => $tx->promo_nama_snapshot,
            'stamp_reward_max_kg_snapshot' => $tx->stamp_reward_max_kg_snapshot,
            'stamps' => $stamps,
            'terbayar' => $paid, 'sisa' => max(0, (int) $tx->total_akhir - $paid),
            'demo' => (bool) $business->is_demo,
            'email_form_enabled' => app(LifecycleService::class)->writable($business) && ($business->is_demo || app(OutboundGuard::class)->allows($business, now())),
        ];
    }
}

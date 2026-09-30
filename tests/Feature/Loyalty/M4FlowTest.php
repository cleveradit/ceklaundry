<?php

namespace Tests\Feature\Loyalty;

use App\Exceptions\StaleQuoteException;
use App\Services\LoyaltySettingsService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\PromoService;
use App\Services\ServiceCatalogService;
use App\Services\TransactionService;
use App\Services\TransactionStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\FoundationTestCase;

class M4FlowTest extends FoundationTestCase
{
    public function test_earning_redemption_promo_compensation_and_public_negative_balance(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $catalog = app(ServiceCatalogService::class);
        $master = $catalog->save($owner, ['nama' => 'Cuci Setrika', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 24, 'berat_minimum' => null, 'is_active' => true]);
        $kg = $catalog->save($owner, ['nama' => 'Cuci Setrika', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 24, 'berat_minimum' => '3.0', 'is_active' => true], null, $branch->id);
        $item = $catalog->save($owner, ['nama' => 'Pewangi', 'satuan' => 'item', 'harga' => 10000, 'durasi_jam' => 1, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        app(LoyaltySettingsService::class)->save($owner, ['is_active' => true, 'stempel_dibutuhkan' => 1, 'master_service_id' => $master->id, 'berat_maks_gratis' => '3.0']);
        $customerId = DB::table('customers')->insertGetId(['business_id' => $business->id, 'nama' => 'Rani', 'no_hp' => '6281234567890', 'created_at' => now(), 'updated_at' => now()]);
        $firstItems = [['service_id' => $item->id, 'jumlah_unit' => 1]];
        $firstQuote = app(PricingService::class)->quote($business->id, $branch->id, $firstItems);
        $firstId = app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer_id' => $customerId,
            'items' => $firstItems, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $firstQuote['fingerprint']]);
        app(PaymentService::class)->store($owner, $firstId, ['request_key' => (string) Str::uuid(), 'jumlah' => 10000, 'metode' => 'tunai']);
        $this->assertSame(1, (int) DB::table('customers')->find($customerId)->stamp_count);
        $this->assertSame(1, DB::table('loyalty_histories')->where('transaction_id', $firstId)->where('jenis', 'perolehan')->count());

        app(PromoService::class)->save($owner, ['nama' => 'Setia 10', 'tipe' => 'persen', 'nilai' => 10, 'minimal_total' => 7000,
            'mulai' => now('Asia/Jakarta')->subDay()->toDateString(), 'selesai' => now('Asia/Jakarta')->addDay()->toDateString(),
            'semua_cabang' => false, 'branch_ids' => [$branch->id], 'is_active' => true]);
        $promoId = (int) DB::table('promos')->where('business_id', $business->id)->value('id');
        $rewardItems = [['service_id' => $kg->id, 'berat_kg' => '2.0']];
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $rewardItems, $customerId, 0, $promoId);
        $this->assertSame(21000, $quote['subtotal']);
        $this->assertSame(14000, $quote['potongan_stempel']);
        $this->assertSame(700, $quote['potongan_promo']);
        $this->assertSame(6300, $quote['total_akhir']);
        $rewardId = app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer_id' => $customerId,
            'items' => $rewardItems, 'reward_item_index' => 0, 'promo_id' => $promoId,
            'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]);
        $reward = DB::table('transactions')->find($rewardId);
        $this->assertSame('Setia 10', $reward->promo_nama_snapshot);
        $this->assertSame(0, (int) DB::table('customers')->find($customerId)->stamp_count);
        app(PaymentService::class)->store($owner, $rewardId, ['request_key' => (string) Str::uuid(), 'jumlah' => 6300, 'metode' => 'tunai']);
        $this->assertSame(0, DB::table('loyalty_histories')->where('transaction_id', $rewardId)->where('jenis', 'perolehan')->count());

        app(TransactionStateMachine::class)->move($owner, $firstId, 'DIBATALKAN', (int) DB::table('transactions')->find($firstId)->version, 'Salah catat');
        $this->assertSame(-1, (int) DB::table('customers')->find($customerId)->stamp_count);
        $response = $this->get('/t/'.$reward->kode_resi);
        $response->assertOk()->assertSee('−1/1')->assertSee('perlu diperoleh kembali');
        app(LoyaltySettingsService::class)->save($owner, ['is_active' => false, 'stempel_dibutuhkan' => 5, 'master_service_id' => null, 'berat_maks_gratis' => null]);
        $this->get('/t/'.$reward->kode_resi)->assertOk()->assertDontSee('Stempel Anda');
        app(TransactionStateMachine::class)->move($owner, $rewardId, 'DIBATALKAN', (int) DB::table('transactions')->find($rewardId)->version, 'Pelanggan batal');
        $this->assertSame(0, (int) DB::table('customers')->find($customerId)->stamp_count);
        $this->assertSame(1, (int) DB::table('loyalty_histories')->where('transaction_id', $rewardId)->where('jenis', 'pengembalian_penukaran')->value('jumlah'));
        $this->assertSame((int) DB::table('loyalty_histories')->where('customer_id', $customerId)->sum('jumlah'), (int) DB::table('customers')->find($customerId)->stamp_count);
    }

    public function test_invalid_settings_and_promo_are_rejected(): void
    {
        [$business, $owner] = $this->tenant();
        [$otherBusiness, $otherOwner] = $this->tenant();
        $foreign = app(ServiceCatalogService::class)->save($otherOwner, ['nama' => 'Hadiah', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 1, 'berat_minimum' => null, 'is_active' => true]);
        try {
            app(LoyaltySettingsService::class)->save($owner, ['is_active' => true, 'stempel_dibutuhkan' => 10, 'master_service_id' => $foreign->id, 'berat_maks_gratis' => '3.0']);
            $this->fail('Hadiah tenant asing diterima.');
        } catch (HttpException) {
            $this->assertFalse((bool) DB::table('loyalty_settings')->where('business_id', $business->id)->value('is_active'));
        }
        $branch = $this->branch($owner);
        try {
            app(PromoService::class)->save($owner, ['nama' => 'Rusak', 'tipe' => 'persen', 'nilai' => 101,
                'mulai' => '2026-09-01', 'selesai' => '2026-09-30', 'semua_cabang' => false,
                'branch_ids' => [$branch->id], 'is_active' => true]);
            $this->fail('Persen 101 diterima.');
        } catch (ValidationException) {
            $this->assertSame(0, DB::table('promos')->where('business_id', $business->id)->count());
        }
        $this->assertNotSame($business->id, $otherBusiness->id);
    }

    public function test_zero_total_promo_awards_once_and_old_snapshot_survives_change(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $master = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Hadiah kg', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 1, 'berat_minimum' => null, 'is_active' => true]);
        $service = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Paket', 'satuan' => 'item', 'harga' => 80000, 'durasi_jam' => 1, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        app(LoyaltySettingsService::class)->save($owner, ['is_active' => true, 'stempel_dibutuhkan' => 10, 'master_service_id' => $master->id, 'berat_maks_gratis' => '3.0']);
        app(PromoService::class)->save($owner, ['nama' => 'Gratis 80', 'tipe' => 'nominal', 'nilai' => 100000, 'minimal_total' => 80000,
            'mulai' => now('Asia/Jakarta')->toDateString(), 'selesai' => now('Asia/Jakarta')->toDateString(),
            'semua_cabang' => true, 'is_active' => true]);
        $promoId = (int) DB::table('promos')->where('business_id', $business->id)->value('id');
        $customerId = DB::table('customers')->insertGetId(['business_id' => $business->id, 'nama' => 'Sari', 'no_hp' => '6281234567891', 'created_at' => now(), 'updated_at' => now()]);
        $items = [['service_id' => $service->id, 'jumlah_unit' => 1]];
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $items, $customerId, null, $promoId);
        $this->assertSame(0, $quote['total_akhir']);
        $this->assertSame(80000, $quote['potongan_promo']);
        $id = app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer_id' => $customerId,
            'items' => $items, 'promo_id' => $promoId, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]);
        $this->assertSame('LUNAS', DB::table('transactions')->find($id)->status_bayar);
        $this->assertSame(0, DB::table('payments')->where('transaction_id', $id)->count());
        $this->assertSame(1, (int) DB::table('customers')->find($customerId)->stamp_count);
        app(PromoService::class)->save($owner, ['nama' => 'Gratis 80', 'tipe' => 'nominal', 'nilai' => 50000, 'minimal_total' => 80000,
            'mulai' => now('Asia/Jakarta')->toDateString(), 'selesai' => now('Asia/Jakarta')->toDateString(),
            'semua_cabang' => true, 'is_active' => true], $promoId);
        $old = DB::table('transactions')->find($id);
        $this->assertSame(80000, (int) $old->potongan_promo);
        $this->assertSame(100000, (int) $old->promo_nilai_snapshot);
        try {
            app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer_id' => $customerId,
                'items' => $items, 'promo_id' => $promoId, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]);
            $this->fail('Quote lama diterima sesudah promo berubah.');
        } catch (StaleQuoteException) {
            $this->assertSame(1, DB::table('transactions')->where('business_id', $business->id)->count());
        }
    }
}

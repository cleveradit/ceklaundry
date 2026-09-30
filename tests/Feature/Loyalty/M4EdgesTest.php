<?php

namespace Tests\Feature\Loyalty;

use App\Services\CustomerMergeService;
use App\Services\LoyaltySettingsService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\PromoService;
use App\Services\ServiceCatalogService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\FoundationTestCase;

class M4EdgesTest extends FoundationTestCase
{
    public function test_master_guard_switch_and_merge_keep_original_ledger_balance(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $catalog = app(ServiceCatalogService::class);
        $master = $catalog->save($owner, ['nama' => 'Cuci', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 1, 'berat_minimum' => null, 'is_active' => true]);
        $item = $catalog->save($owner, ['nama' => 'Paket', 'satuan' => 'item', 'harga' => 1000, 'durasi_jam' => 1, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $settings = app(LoyaltySettingsService::class);
        $settings->save($owner, ['is_active' => true, 'stempel_dibutuhkan' => 10, 'master_service_id' => $master->id, 'berat_maks_gratis' => '3.0']);
        try {
            $catalog->save($owner, ['nama' => 'Cuci', 'satuan' => 'item', 'harga' => 7000, 'durasi_jam' => 1, 'berat_minimum' => null, 'is_active' => true], $master->id);
            $this->fail('Master hadiah aktif berubah satuan.');
        } catch (ValidationException) {
            $this->assertSame('kg', DB::table('master_services')->find($master->id)->satuan);
        }
        $source = DB::table('customers')->insertGetId(['business_id' => $business->id, 'nama' => 'Sumber', 'no_hp' => '6281234567890', 'created_at' => now(), 'updated_at' => now()]);
        $target = DB::table('customers')->insertGetId(['business_id' => $business->id, 'nama' => 'Tujuan', 'no_hp' => '6281234567891', 'created_at' => now(), 'updated_at' => now()]);
        $items = [['service_id' => $item->id, 'jumlah_unit' => 1]];
        foreach ([$source, $target] as $customerId) {
            $quote = app(PricingService::class)->quote($business->id, $branch->id, $items);
            $id = app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer_id' => $customerId,
                'items' => $items, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]);
            app(PaymentService::class)->store($owner, $id, ['request_key' => (string) Str::uuid(), 'jumlah' => 1000, 'metode' => 'tunai']);
        }
        $settings->save($owner, ['is_active' => false, 'stempel_dibutuhkan' => 5, 'master_service_id' => null, 'berat_maks_gratis' => null]);
        app(CustomerMergeService::class)->merge($owner, $source, $target);
        $this->assertSame(2, (int) DB::table('customers')->find($target)->stamp_count);
        $this->assertSame(2, (int) DB::table('loyalty_histories')->where('customer_id', $target)->sum('jumlah'));
        $this->assertSame(0, DB::table('loyalty_histories')->where('customer_id', $source)->count());
        $settings->save($owner, ['is_active' => true, 'stempel_dibutuhkan' => 5, 'master_service_id' => $master->id, 'berat_maks_gratis' => '3.0']);
        $this->assertSame(2, (int) DB::table('customers')->find($target)->stamp_count);
    }

    public function test_promo_scope_minimum_and_half_up_are_checked_from_current_quote(): void
    {
        [$business, $owner] = $this->tenant();
        $firstBranch = $this->branch($owner);
        $secondBranch = $this->branch($owner, 'Kedua');
        $service = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Kecil', 'satuan' => 'item', 'harga' => 5, 'durasi_jam' => 1, 'berat_minimum' => null, 'is_active' => true], null, $firstBranch->id);
        $secondService = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Kecil', 'satuan' => 'item', 'harga' => 5, 'durasi_jam' => 1, 'berat_minimum' => null, 'is_active' => true], null, $secondBranch->id);
        $today = now('Asia/Jakarta')->toDateString();
        app(PromoService::class)->save($owner, ['nama' => 'Sepuluh', 'tipe' => 'persen', 'nilai' => 10, 'minimal_total' => 5,
            'mulai' => $today, 'selesai' => $today, 'semua_cabang' => false, 'branch_ids' => [$firstBranch->id], 'is_active' => true]);
        $promoId = (int) DB::table('promos')->where('business_id', $business->id)->value('id');
        $items = [['service_id' => $service->id, 'jumlah_unit' => 1]];
        $quote = app(PricingService::class)->quote($business->id, $firstBranch->id, $items, null, null, $promoId);
        $this->assertSame(1, $quote['potongan_promo']);
        $this->assertSame(4, $quote['total_akhir']);
        try {
            app(PricingService::class)->quote($business->id, $secondBranch->id, [['service_id' => $secondService->id, 'jumlah_unit' => 1]], null, null, $promoId);
            $this->fail('Promo cabang lain diterima.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('promo_id', $exception->errors());
        }
        app(PromoService::class)->save($owner, ['nama' => 'Sepuluh', 'tipe' => 'persen', 'nilai' => 10, 'minimal_total' => 6,
            'mulai' => $today, 'selesai' => $today, 'semua_cabang' => false, 'branch_ids' => [$firstBranch->id], 'is_active' => true], $promoId);
        $this->expectException(ValidationException::class);
        app(PricingService::class)->quote($business->id, $firstBranch->id, $items, null, null, $promoId);
    }
}

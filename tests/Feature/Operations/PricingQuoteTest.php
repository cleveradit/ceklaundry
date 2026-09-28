<?php

namespace Tests\Feature\Operations;

use App\Services\PricingService;
use App\Services\ServiceCatalogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\FoundationTestCase;

class PricingQuoteTest extends FoundationTestCase
{
    public function test_minimum_half_up_and_branch_scope(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $other = $this->branch($owner, 'Cabang Kedua');
        $kg = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 48, 'berat_minimum' => '3.0', 'is_active' => true], null, $branch->id);
        $cent = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Uji Pembulatan', 'satuan' => 'kg', 'harga' => 5, 'durasi_jam' => 6, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $quote = app(PricingService::class)->quote($business->id, $branch->id, [['service_id' => $kg->id, 'berat_kg' => '2.0'], ['service_id' => $cent->id, 'berat_kg' => '0.1']]);
        $this->assertSame(21001, $quote['total_akhir']);
        $this->assertSame('2.0', $quote['items'][0]['berat_kg']);
        $this->assertSame('3.0', $quote['items'][0]['berat_minimum_snapshot']);
        $this->assertSame(48, max(array_column($quote['items'], 'durasi_jam_snapshot')));
        $this->expectException(ValidationException::class);
        app(PricingService::class)->quote($business->id, $other->id, [['service_id' => $kg->id, 'berat_kg' => '2.0']]);
    }

    public function test_invalid_quantities_and_overflow_do_not_produce_quote(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $service = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 1, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        foreach (['0', '-1', '1.23', '10000.0'] as $weight) {
            try {
                app(PricingService::class)->quote($business->id, $branch->id, [['service_id' => $service->id, 'berat_kg' => $weight]]);
                $this->fail('Berat tidak valid diterima: '.$weight);
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        DB::table('services')->where('id', $service->id)->update(['harga' => 1000000]);
        $this->expectException(ValidationException::class);
        app(PricingService::class)->quote($business->id, $branch->id, [['service_id' => $service->id, 'berat_kg' => '9999.9']]);
    }
}

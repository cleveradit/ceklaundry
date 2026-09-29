<?php

namespace Tests\Feature\Public;

use App\Services\PricingService;
use App\Services\ServiceCatalogService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\FoundationTestCase;

class ReceiptLookupTest extends FoundationTestCase
{
    public function test_masked_receipt_and_public_print_share_safe_payload(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $service = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'item', 'harga' => 25000, 'durasi_jam' => 24, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $items = [['service_id' => $service->id, 'jumlah_unit' => 1]];
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $items);
        $id = app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer' => ['nama' => 'Rani Rahasia', 'no_hp' => '081398765432', 'email' => 'rani@example.test'], 'items' => $items, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]);
        $code = DB::table('transactions')->find($id)->kode_resi;
        $this->get('/')->assertOk()->assertSee('Cek Status')->assertDontSee('resources/js/app.tsx');
        $this->get('/check?kode_resi='.strtolower($code))->assertRedirect('/t/'.$code);
        $this->get('/t/'.$code)->assertOk()->assertSee('Ran***')->assertDontSee('Rani Rahasia')->assertDontSee('rani@example.test')->assertDontSee('6281398765432')->assertHeader('Referrer-Policy', 'no-referrer');
        $this->get('/t/'.$code.'/print')->assertOk()->assertSee('Ran***')->assertDontSee('Rani Rahasia')->assertDontSee('rani@example.test');
        $this->actingAs($owner)->get('/app/transactions/'.$id.'/print')->assertOk()->assertSee('Rani Rahasia');
        $this->get('/t/XXXXXX')->assertStatus(404)->assertSee('Kode resi tidak ditemukan')
            ->assertSee('action="/check"', false)->assertSee('Cek Status');
        $this->get('/check?kode_resi=XXXXXX')->assertRedirect('/');
        $this->get('/')->assertOk()->assertSee('Kode resi tidak ditemukan, periksa kembali resi Anda')
            ->assertSee('value="XXXXXX"', false);
    }
}

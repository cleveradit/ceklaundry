<?php

namespace Tests\Feature\Operations;

use App\Services\PricingService;
use App\Services\ServiceCatalogService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\FoundationTestCase;

class TransactionCreateTest extends FoundationTestCase
{
    public function test_retry_stale_quote_and_failed_initial_payment_are_atomic(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $service = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'item', 'harga' => 50000, 'durasi_jam' => 24, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $items = [['service_id' => $service->id, 'jumlah_unit' => 1]];
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $items);
        $request = ['branch_id' => $branch->id, 'customer' => ['nama' => 'Rani', 'no_hp' => '081398765432'], 'items' => $items, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']];
        $id = app(TransactionService::class)->create($owner, $request);
        $this->assertSame($id, app(TransactionService::class)->create($owner, $request));
        $this->assertSame(1, DB::table('transactions')->where('business_id', $business->id)->count());
        try {
            app(TransactionService::class)->create($owner, [...$request, 'catatan_kondisi' => 'berbeda']);
            $this->fail('Key sama dengan payload berbeda diterima.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
        DB::table('services')->where('id', $service->id)->update(['harga' => 60000]);
        try {
            app(TransactionService::class)->create($owner, [...$request, 'request_key' => (string) Str::uuid(), 'customer' => ['nama' => 'Baru', 'no_hp' => '081398765433']]);
            $this->fail('Quote usang diterima.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
            $this->assertSame(1, DB::table('transactions')->where('business_id', $business->id)->count());
        }
        $this->actingAs($owner)->postJson('/app/transactions', [...$request, 'request_key' => (string) Str::uuid(), 'customer' => ['nama' => 'Baru', 'no_hp' => '081398765433']])
            ->assertStatus(409)->assertJsonPath('quote.total_akhir', 60000);
        $freshQuote = app(PricingService::class)->quote($business->id, $branch->id, $items);
        try {
            app(TransactionService::class)->create($owner, [...$request, 'request_key' => (string) Str::uuid(), 'customer' => ['nama' => 'Baru', 'no_hp' => '081398765433'], 'quote_fingerprint' => $freshQuote['fingerprint'], 'initial_payment' => ['jumlah' => 30000, 'metode' => 'tunai']]);
            $this->fail('DP awal saat saklar mati diterima.');
        } catch (ValidationException) {
            $this->assertSame(1, DB::table('transactions')->where('business_id', $business->id)->count());
            $this->assertSame(0, DB::table('customers')->where('no_hp', '6281398765433')->count());
        }
    }
}

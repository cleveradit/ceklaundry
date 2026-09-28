<?php

namespace Tests\Feature\Operations;

use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\ServiceCatalogService;
use App\Services\TransactionService;
use App\Services\TransactionStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\FoundationTestCase;

class M2JourneyTest extends FoundationTestCase
{
    public function test_customer_to_receipt_dp_and_pickup(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $kg = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci Setrika', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 48, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $item = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Bed Cover', 'satuan' => 'item', 'harga' => 25000, 'durasi_jam' => 6, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $lines = [['service_id' => $kg->id, 'berat_kg' => '3.5'], ['service_id' => $item->id, 'jumlah_unit' => 2]];
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $lines);
        $this->assertSame(74500, $quote['total_akhir']);
        $id = app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer' => ['nama' => 'Rani', 'no_hp' => '081234567890', 'email' => 'rani@example.test'], 'items' => $lines, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint'], 'catatan_kondisi' => 'noda di kerah']);
        $tx = DB::table('transactions')->find($id);
        $this->assertSame('DITERIMA', $tx->status);
        $this->assertSame('BELUM_BAYAR', $tx->status_bayar);
        $this->assertSame(1, DB::table('status_histories')->where('transaction_id', $id)->count());
        $this->assertSame(2, DB::table('transaction_items')->where('transaction_id', $id)->count());
        $this->assertSame(1, DB::table('transactions')->where('kode_resi', $tx->kode_resi)->count());
        $this->assertSame('rani@example.test', $tx->notification_email);
        $this->assertSame('6281234567890', DB::table('customers')->find($tx->customer_id)->no_hp);

        DB::table('business_settings')->where('business_id', $business->id)->update(['dp_enabled' => true]);
        app(PaymentService::class)->store($owner, $id, ['request_key' => (string) Str::uuid(), 'jumlah' => 30000, 'metode' => 'tunai']);
        $this->assertSame('DP', DB::table('transactions')->find($id)->status_bayar);
        app(TransactionStateMachine::class)->move($owner, $id, 'DIPROSES', 2);
        app(TransactionStateMachine::class)->move($owner, $id, 'SIAP_DIAMBIL', 3);
        $this->expectException(HttpException::class);
        app(TransactionStateMachine::class)->move($owner, $id, 'SUDAH_DIAMBIL', 4);
    }

    public function test_payoff_then_pickup_and_public_masking(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $service = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'kg', 'harga' => 74500, 'durasi_jam' => 24, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $items = [['service_id' => $service->id, 'berat_kg' => '1.0']];
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $items);
        $id = app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer' => ['nama' => 'Rani', 'no_hp' => '081398765432'], 'items' => $items, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]);
        DB::table('business_settings')->where('business_id', $business->id)->update(['dp_enabled' => true]);
        app(PaymentService::class)->store($owner, $id, ['request_key' => (string) Str::uuid(), 'jumlah' => 30000, 'metode' => 'tunai']);
        app(TransactionStateMachine::class)->move($owner, $id, 'DIPROSES', 2);
        app(TransactionStateMachine::class)->move($owner, $id, 'SIAP_DIAMBIL', 3);
        app(PaymentService::class)->store($owner, $id, ['request_key' => (string) Str::uuid(), 'jumlah' => 44500, 'metode' => 'transfer']);
        app(TransactionStateMachine::class)->move($owner, $id, 'SUDAH_DIAMBIL', 5);
        $tx = DB::table('transactions')->find($id);
        $this->assertSame('LUNAS', $tx->status_bayar);
        $this->assertNotNull($tx->waktu_diambil);
        $this->get('/t/'.$tx->kode_resi)->assertOk()->assertSee('Ran***')->assertDontSee('6281398765432');
    }
}

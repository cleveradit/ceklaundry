<?php

namespace Tests\Feature\Operations;

use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\ServiceCatalogService;
use App\Services\TransactionService;
use App\Services\TransactionStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\FoundationTestCase;

class PaymentTest extends FoundationTestCase
{
    public function test_zero_total_fixture_is_lunas_without_payment_and_can_be_picked_up(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'subtotal' => 0, 'total_akhir' => 0, 'status_bayar' => 'LUNAS',
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now(),
        ]);
        app(TransactionStateMachine::class)->move($owner, $id, 'SUDAH_DIAMBIL', 1);
        $this->assertSame('SUDAH_DIAMBIL', DB::table('transactions')->find($id)->status);
        $this->assertSame(0, DB::table('payments')->where('transaction_id', $id)->count());
    }

    public function test_dp_toggle_replay_and_overpay(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $service = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'item', 'harga' => 100000, 'durasi_jam' => 24, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $items = [['service_id' => $service->id, 'jumlah_unit' => 1]];
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $items);
        $id = app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer' => ['nama' => 'Rani', 'no_hp' => '081398765432'], 'items' => $items, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]);
        $payment = ['request_key' => (string) Str::uuid(), 'jumlah' => 30000, 'metode' => 'tunai'];
        try {
            app(PaymentService::class)->store($owner, $id, $payment);
            $this->fail('DP baru diterima ketika nonaktif.');
        } catch (ValidationException) {
            $this->assertSame(0, DB::table('payments')->where('transaction_id', $id)->count());
        }
        DB::table('business_settings')->where('business_id', $business->id)->update(['dp_enabled' => true]);
        $first = app(PaymentService::class)->store($owner, $id, $payment);
        try {
            app(PaymentService::class)->store($owner, $id, ['request_key' => (string) Str::uuid(), 'jumlah' => 1000, 'metode' => 'tunai', 'waktu' => '2020-01-01 00:00:00']);
            $this->fail('Tanggal pembayaran dari client diterima.');
        } catch (ValidationException) {
            $this->assertSame(1, DB::table('payments')->where('transaction_id', $id)->count());
        }
        DB::table('business_settings')->where('business_id', $business->id)->update(['dp_enabled' => false]);
        $replay = app(PaymentService::class)->store($owner, $id, $payment);
        $this->assertSame($first->id, $replay->id);
        app(PaymentService::class)->store($owner, $id, ['request_key' => (string) Str::uuid(), 'jumlah' => 20000, 'metode' => 'transfer']);
        $this->assertSame('DP', DB::table('transactions')->find($id)->status_bayar);
        try {
            app(PaymentService::class)->store($owner, $id, ['request_key' => (string) Str::uuid(), 'jumlah' => 60000, 'metode' => 'tunai']);
            $this->fail('Pembayaran berlebih diterima.');
        } catch (ValidationException) {
            $this->assertSame(50000, (int) DB::table('payments')->where('transaction_id', $id)->sum('jumlah'));
        }
        app(PaymentService::class)->store($owner, $id, ['request_key' => (string) Str::uuid(), 'jumlah' => 50000, 'metode' => 'tunai']);
        $this->assertSame('LUNAS', DB::table('transactions')->find($id)->status_bayar);
    }
}

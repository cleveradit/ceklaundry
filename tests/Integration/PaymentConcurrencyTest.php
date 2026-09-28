<?php

namespace Tests\Integration;

use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\ServiceCatalogService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\ConcurrentTestCase;

class PaymentConcurrencyTest extends ConcurrentTestCase
{
    public function test_dp_toggle_and_first_partial_payment_follow_root_lock_order(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        DB::table('business_settings')->where('business_id', $business->id)->update(['dp_enabled' => true]);
        $offFirst = $this->transaction($business, $owner, $branch->id);
        DB::beginTransaction();
        DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
        DB::table('business_settings')->where('business_id', $business->id)->update(['dp_enabled' => false]);
        $payment = $this->startWorker(['actor' => $owner->id, 'operation' => 'payment', 'transaction' => $offFirst, 'key' => (string) Str::uuid(), 'amount' => 3000]);
        $this->assertBlocked($payment);
        DB::commit();
        $payment->wait();
        $this->assertTrue($payment->isSuccessful(), $payment->getErrorOutput());
        $this->assertSame(422, json_decode($payment->getOutput(), true)['status']);
        $this->assertSame(0, DB::table('payments')->where('transaction_id', $offFirst)->count());

        DB::table('business_settings')->where('business_id', $business->id)->update(['dp_enabled' => true]);
        $paymentFirst = $this->transaction($business, $owner, $branch->id);
        DB::beginTransaction();
        DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
        $off = $this->startWorker(['actor' => $owner->id, 'operation' => 'dp-off']);
        $this->assertBlocked($off);
        app(PaymentService::class)->store($owner, $paymentFirst, ['request_key' => (string) Str::uuid(), 'jumlah' => 3000, 'metode' => 'tunai']);
        DB::commit();
        $off->wait();
        $this->assertTrue($off->isSuccessful(), $off->getErrorOutput());
        $this->assertSame(200, json_decode($off->getOutput(), true)['status']);
        $this->assertSame('DP', DB::table('transactions')->find($paymentFirst)->status_bayar);
        $this->assertSame(3000, (int) DB::table('payments')->where('transaction_id', $paymentFirst)->sum('jumlah'));
        $this->assertSame(0, (int) DB::table('business_settings')->where('business_id', $business->id)->value('dp_enabled'));
    }

    public function test_two_payments_for_same_remaining_balance_do_not_overpay(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $service = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'item', 'harga' => 50000, 'durasi_jam' => 24, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $items = [['service_id' => $service->id, 'jumlah_unit' => 1]];
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $items);
        $id = app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer' => ['nama' => 'Rani', 'no_hp' => '081398765432'], 'items' => $items, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]);
        DB::beginTransaction();
        DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
        $a = $this->startWorker(['actor' => $owner->id, 'operation' => 'payment', 'transaction' => $id, 'key' => (string) Str::uuid(), 'amount' => 50000]);
        $b = $this->startWorker(['actor' => $owner->id, 'operation' => 'payment', 'transaction' => $id, 'key' => (string) Str::uuid(), 'amount' => 50000]);
        $this->assertBlocked($a);
        $this->assertBlocked($b);
        DB::commit();
        $a->wait();
        $b->wait();
        $this->assertTrue($a->isSuccessful(), $a->getErrorOutput());
        $this->assertTrue($b->isSuccessful(), $b->getErrorOutput());
        $statuses = [json_decode($a->getOutput(), true)['status'], json_decode($b->getOutput(), true)['status']];
        sort($statuses);
        $this->assertSame([200, 422], $statuses);
        $this->assertSame(50000, (int) DB::table('payments')->where('transaction_id', $id)->sum('jumlah'));
        $this->assertSame('LUNAS', DB::table('transactions')->find($id)->status_bayar);
    }
}

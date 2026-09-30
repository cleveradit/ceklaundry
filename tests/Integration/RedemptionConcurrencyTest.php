<?php

namespace Tests\Integration;

use App\Services\LoyaltySettingsService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\ServiceCatalogService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\ConcurrentTestCase;

class RedemptionConcurrencyTest extends ConcurrentTestCase
{
    public function test_two_creates_cannot_spend_the_same_stamp(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $master = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 24, 'berat_minimum' => null, 'is_active' => true]);
        $kg = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 24, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        app(LoyaltySettingsService::class)->save($owner, ['is_active' => true, 'stempel_dibutuhkan' => 1, 'master_service_id' => $master->id, 'berat_maks_gratis' => '1.0']);
        $customerId = DB::table('customers')->insertGetId(['business_id' => $business->id, 'nama' => 'Rani', 'no_hp' => '6281234567890', 'created_at' => now(), 'updated_at' => now()]);
        $items = [['service_id' => $kg->id, 'berat_kg' => '2.0']];
        $first = app(PricingService::class)->quote($business->id, $branch->id, $items);
        $id = app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer_id' => $customerId,
            'items' => $items, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $first['fingerprint']]);
        app(PaymentService::class)->store($owner, $id, ['request_key' => (string) Str::uuid(), 'jumlah' => 14000, 'metode' => 'tunai']);
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $items, $customerId, 0);
        $data = ['branch_id' => $branch->id, 'customer_id' => $customerId, 'items' => $items,
            'reward_item_index' => 0, 'quote_fingerprint' => $quote['fingerprint']];
        DB::beginTransaction();
        DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
        $a = $this->startWorker(['actor' => $owner->id, 'operation' => 'create', 'data' => [...$data, 'request_key' => (string) Str::uuid()]]);
        $b = $this->startWorker(['actor' => $owner->id, 'operation' => 'create', 'data' => [...$data, 'request_key' => (string) Str::uuid()]]);
        $this->assertBlocked($a);
        $this->assertBlocked($b);
        DB::commit();
        $a->wait();
        $b->wait();
        $this->assertTrue($a->isSuccessful(), $a->getErrorOutput());
        $this->assertTrue($b->isSuccessful(), $b->getErrorOutput());
        $statuses = [json_decode($a->getOutput(), true)['status'], json_decode($b->getOutput(), true)['status']];
        sort($statuses);
        $this->assertSame([200, 409], $statuses);
        $this->assertSame(1, DB::table('loyalty_histories')->where('jenis', 'penukaran')->count());
        $this->assertSame(0, (int) DB::table('customers')->find($customerId)->stamp_count);
        $this->assertSame(0, (int) DB::table('loyalty_histories')->where('customer_id', $customerId)->sum('jumlah'));
    }
}

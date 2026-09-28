<?php

namespace Tests\Integration;

use App\Services\BranchService;
use App\Services\PricingService;
use App\Services\ServiceCatalogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\ConcurrentTestCase;

class TransactionCreateConcurrencyTest extends ConcurrentTestCase
{
    public function test_two_identical_create_requests_commit_only_one_transaction(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $service = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Setrika', 'satuan' => 'item', 'harga' => 7000, 'durasi_jam' => 12, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $items = [['service_id' => $service->id, 'jumlah_unit' => 1]];
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $items);
        $data = ['branch_id' => $branch->id, 'customer' => ['nama' => 'Rani', 'no_hp' => '081398765432'], 'items' => $items,
            'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']];

        DB::beginTransaction();
        DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
        $first = $this->startWorker(['actor' => $owner->id, 'operation' => 'create', 'data' => $data]);
        $second = $this->startWorker(['actor' => $owner->id, 'operation' => 'create', 'data' => $data]);
        $this->assertBlocked($first);
        $this->assertBlocked($second);
        DB::commit();
        $this->workerResult($first, 200);
        $this->workerResult($second, 200);
        $this->assertSame(1, DB::table('transactions')->where('business_id', $business->id)->count());
        $this->assertSame(1, DB::table('customers')->where('business_id', $business->id)->count());
        $this->assertSame(1, DB::table('status_histories')->count());
    }

    public function test_branch_deactivation_before_create_lock_prevents_active_transaction(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $service = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'item', 'harga' => 7000, 'durasi_jam' => 24, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $items = [['service_id' => $service->id, 'jumlah_unit' => 1]];
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $items);
        DB::beginTransaction();
        DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
        $worker = $this->startWorker(['actor' => $owner->id, 'operation' => 'create', 'data' => ['branch_id' => $branch->id, 'customer' => ['nama' => 'Rani', 'no_hp' => '081398765432'], 'items' => $items, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]]);
        $this->assertBlocked($worker);
        app(BranchService::class)->save($owner, ['nama' => $branch->nama, 'alamat' => $branch->alamat, 'telepon' => $branch->telepon, 'is_active' => false], $branch->id);
        DB::commit();
        $this->workerResult($worker, 403);
        $this->assertSame(0, DB::table('transactions')->where('business_id', $business->id)->count());
    }
}

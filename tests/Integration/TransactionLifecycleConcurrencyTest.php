<?php

namespace Tests\Integration;

use App\Services\PricingService;
use App\Services\ServiceCatalogService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\ConcurrentTestCase;

class TransactionLifecycleConcurrencyTest extends ConcurrentTestCase
{
    public function test_stale_parallel_status_requests_cannot_jump_or_duplicate_history(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $service = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'item', 'harga' => 7000, 'durasi_jam' => 24, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $items = [['service_id' => $service->id, 'jumlah_unit' => 1]];
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $items);
        $id = app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer' => ['nama' => 'Rani', 'no_hp' => '081398765432'], 'items' => $items, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]);
        DB::beginTransaction();
        DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
        $a = $this->startWorker(['actor' => $owner->id, 'operation' => 'status', 'transaction' => $id, 'target' => 'DIPROSES', 'version' => 1]);
        $b = $this->startWorker(['actor' => $owner->id, 'operation' => 'status', 'transaction' => $id, 'target' => 'SIAP_DIAMBIL', 'version' => 1]);
        $this->assertBlocked($a);
        $this->assertBlocked($b);
        DB::commit();
        $a->wait();
        $b->wait();
        $this->assertTrue($a->isSuccessful(), $a->getErrorOutput());
        $this->assertTrue($b->isSuccessful(), $b->getErrorOutput());
        $codes = [json_decode($a->getOutput(), true)['status'], json_decode($b->getOutput(), true)['status']];
        $this->assertContains(200, $codes);
        $this->assertSame(1, count(array_filter($codes, fn ($status) => $status === 200)));
        $this->assertSame(2, DB::table('status_histories')->where('transaction_id', $id)->count());
        $this->assertSame('DIPROSES', DB::table('transactions')->find($id)->status);
    }
}

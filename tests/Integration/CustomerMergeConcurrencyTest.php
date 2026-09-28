<?php

namespace Tests\Integration;

use App\Services\CustomerService;
use Illuminate\Support\Facades\DB;
use Tests\ConcurrentTestCase;

class CustomerMergeConcurrencyTest extends ConcurrentTestCase
{
    public function test_opposite_merges_serialize_without_orphan(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $a = app(CustomerService::class)->save($owner, ['nama' => 'A', 'no_hp' => '081355500001']);
        $b = app(CustomerService::class)->save($owner, ['nama' => 'B', 'no_hp' => '081355500002']);
        $transaction = $this->transaction($business, $owner, $branch->id, ['customer_id' => $a]);
        DB::beginTransaction();
        DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
        $one = $this->startWorker(['actor' => $owner->id, 'operation' => 'merge', 'source' => $a, 'target' => $b]);
        $two = $this->startWorker(['actor' => $owner->id, 'operation' => 'merge', 'source' => $b, 'target' => $a]);
        $this->assertBlocked($one);
        $this->assertBlocked($two);
        DB::commit();
        $one->wait();
        $two->wait();
        $this->assertTrue($one->isSuccessful(), $one->getErrorOutput());
        $this->assertTrue($two->isSuccessful(), $two->getErrorOutput());
        $codes = [json_decode($one->getOutput(), true)['status'], json_decode($two->getOutput(), true)['status']];
        sort($codes);
        $this->assertSame([200, 404], $codes);
        $this->assertSame(1, DB::table('customers')->where('business_id', $business->id)->whereIn('id', [$a, $b])->count());
        $survivor = DB::table('transactions')->find($transaction)->customer_id;
        $this->assertNotNull(DB::table('customers')->find($survivor));
        $this->assertSame(1, DB::table('audit_logs')->where('aksi', 'pelanggan.gabung')->count());
    }
}

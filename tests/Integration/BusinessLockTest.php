<?php

namespace Tests\Integration;

use App\Models\Business;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\ConcurrentTestCase;

class BusinessLockTest extends ConcurrentTestCase
{
    public function test_parallel_login_limiter_allows_exactly_five(): void
    {
        $actor = $this->developer();
        $lock = Cache::lock('auth-limit:login', 10);
        $this->assertTrue($lock->get());
        $children = [];
        $now = now()->toDateTimeString();
        for ($i = 0; $i < 6; $i++) {
            $children[] = $this->startWorker(['operation' => 'limiter', 'actor' => $actor->id, 'now' => $now]);
        }
        $lock->release();
        $statuses = [];
        foreach ($children as $child) {
            $child->wait();
            $this->assertTrue($child->isSuccessful(), $child->getErrorOutput());
            $statuses[] = json_decode($child->getOutput(), true)['status'];
        }
        sort($statuses);
        $this->assertSame([200, 200, 200, 200, 200, 429], $statuses);
    }

    public function test_same_tenant_waits_other_tenant_proceeds_and_actor_is_rechecked(): void
    {
        [$a, $ownerA] = $this->tenant();
        [$b, $ownerB] = $this->tenant();
        DB::beginTransaction();
        Business::lockForUpdate()->findOrFail($a->id);
        $blocked = $this->startWorker(['operation' => 'branch', 'actor' => $ownerA->id]);
        $this->assertBlocked($blocked);
        $independent = $this->startWorker(['operation' => 'branch', 'actor' => $ownerB->id]);
        $this->workerResult($independent, 200);
        $ownerA->forceFill(['is_active' => false])->save();
        DB::commit();
        $this->workerResult($blocked, 403);
        $this->assertSame(0, DB::table('branches')->where('business_id', $a->id)->count());
        $this->assertSame(1, DB::table('branches')->where('business_id', $b->id)->count());
    }
}

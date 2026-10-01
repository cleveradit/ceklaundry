<?php

namespace Tests\Integration;

use App\Models\Business;
use App\Services\DemoProvisioner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\ConcurrentTestCase;

class DemoConcurrencyTest extends ConcurrentTestCase
{
    public function test_parallel_requests_allow_only_three_demos_per_ip(): void
    {
        $developer = $this->developer();
        $ip = '198.51.100.117';
        $fingerprint = hash_hmac('sha256', $ip, (string) config('app.key'));
        $lock = Cache::lock('demo:lock:'.$fingerprint, 10);
        $this->assertTrue($lock->get());
        $children = [];
        $at = now('Asia/Jakarta')->toDateTimeString();
        for ($index = 0; $index < 4; $index++) {
            $children[] = $this->startWorker(['operation' => 'demo-limiter', 'actor' => $developer->id, 'ip' => $ip, 'now' => $at]);
        }
        $lock->release();
        $statuses = [];
        foreach ($children as $child) {
            $child->wait();
            $this->assertTrue($child->isSuccessful(), $child->getErrorOutput());
            $statuses[] = json_decode($child->getOutput(), true)['status'];
        }
        sort($statuses);
        $this->assertSame([200, 200, 200, 429], $statuses);
    }

    public function test_two_purges_serialize_and_leave_normal_tenant_intact(): void
    {
        $developer = $this->developer();
        [$normal] = $this->tenant($developer);
        $demo = app(DemoProvisioner::class)->provision()['business'];
        DB::table('businesses')->where('id', $demo->id)->update(['demo_expires_at' => now()->subSecond()]);
        DB::beginTransaction();
        Business::query()->lockForUpdate()->findOrFail($demo->id);
        $first = $this->startWorker(['operation' => 'demo-purge', 'actor' => $developer->id, 'business' => $demo->id]);
        $second = $this->startWorker(['operation' => 'demo-purge', 'actor' => $developer->id, 'business' => $demo->id]);
        $this->assertBlocked($first);
        $this->assertBlocked($second);
        DB::commit();
        $this->workerResult($first, 200);
        $this->workerResult($second, 200);
        $this->assertFalse(DB::table('businesses')->where('id', $demo->id)->exists());
        $this->assertTrue(DB::table('businesses')->where('id', $normal->id)->exists());
        $this->assertSame(0, DB::table('notification_logs')->where('business_id', $demo->id)->count());
    }
}

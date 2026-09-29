<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\ConcurrentTestCase;

class ManualNotificationConcurrencyTest extends ConcurrentTestCase
{
    public function test_two_manual_requests_with_same_uuid_create_one_log_and_job(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
            'notification_email' => 'pelanggan@example.test',
        ]);
        $key = (string) Str::uuid();
        DB::beginTransaction();
        DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
        $a = $this->startWorker(['actor' => $owner->id, 'operation' => 'manual-email', 'transaction' => $id, 'key' => $key]);
        $b = $this->startWorker(['actor' => $owner->id, 'operation' => 'manual-email', 'transaction' => $id, 'key' => $key]);
        $this->assertBlocked($a);
        $this->assertBlocked($b);
        DB::commit();
        $this->workerResult($a, 200);
        $this->workerResult($b, 200);
        $this->assertSame(1, DB::table('notification_logs')->where('transaction_id', $id)->where('is_manual', true)->count());
        $this->assertSame(1, DB::table('jobs')->where('payload->business_id', $business->id)->count());
        $this->assertSame(0, DB::table('transactions')->find($id)->reminder_count);
    }
}

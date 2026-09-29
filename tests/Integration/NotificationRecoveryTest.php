<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Tests\ConcurrentTestCase;

class NotificationRecoveryTest extends ConcurrentTestCase
{
    public function test_two_workers_claim_one_log_and_call_provider_once(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
            'notification_email' => 'pelanggan@example.test',
        ]);
        $log = $this->notification($business, $id, ['tujuan' => 'pelanggan@example.test']);
        DB::beginTransaction();
        DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
        $a = $this->startWorker(['actor' => $owner->id, 'operation' => 'send-notification', 'business' => $business->id, 'log' => $log]);
        $b = $this->startWorker(['actor' => $owner->id, 'operation' => 'send-notification', 'business' => $business->id, 'log' => $log]);
        $this->assertBlocked($a);
        $this->assertBlocked($b);
        DB::commit();
        $this->workerResult($a, 200);
        $this->workerResult($b, 200);
        $result = DB::table('notification_logs')->find($log);
        $this->assertSame('berhasil', $result->status);
        $this->assertSame(1, $result->attempt_count);
    }
}

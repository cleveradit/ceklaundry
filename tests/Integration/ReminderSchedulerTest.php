<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Tests\ConcurrentTestCase;

class ReminderSchedulerTest extends ConcurrentTestCase
{
    public function test_two_scheduler_processes_reserve_only_one_number(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta')->subDays(3),
            'notification_email' => 'pelanggan@example.test',
        ]);
        DB::beginTransaction();
        DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
        $a = $this->startWorker(['actor' => $owner->id, 'operation' => 'reminder']);
        $b = $this->startWorker(['actor' => $owner->id, 'operation' => 'reminder']);
        $this->assertBlocked($a);
        $this->assertBlocked($b);
        DB::commit();
        $this->workerResult($a, 200);
        $this->workerResult($b, 200);
        $this->assertSame(1, DB::table('transactions')->find($id)->reminder_count);
        $this->assertSame(1, DB::table('notification_logs')->where('transaction_id', $id)->where('tipe', 'pengingat')->count());
    }
}

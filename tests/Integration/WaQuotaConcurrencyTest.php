<?php

namespace Tests\Integration;

use Illuminate\Support\Facades\DB;
use Tests\ConcurrentTestCase;

class WaQuotaConcurrencyTest extends ConcurrentTestCase
{
    public function test_two_ready_transitions_compete_for_one_monthly_slot(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $business->forceFill(['wa_enabled' => true, 'wa_provider' => 'fonnte', 'wa_token' => 'test-token',
            'wa_sender_number' => '6281234567890'])->save();
        DB::table('business_settings')->where('business_id', $business->id)->update(['wa_monthly_limit' => 1]);
        $first = $this->transaction($business, $owner, $branch->id, [
            'status' => 'DIPROSES', 'version' => 2, 'notification_email' => 'a@example.test',
        ]);
        $second = $this->transaction($business, $owner, $branch->id, [
            'status' => 'DIPROSES', 'version' => 2, 'notification_email' => 'b@example.test',
        ]);
        DB::beginTransaction();
        DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
        $a = $this->startWorker(['actor' => $owner->id, 'operation' => 'status', 'transaction' => $first,
            'target' => 'SIAP_DIAMBIL', 'version' => 2]);
        $b = $this->startWorker(['actor' => $owner->id, 'operation' => 'status', 'transaction' => $second,
            'target' => 'SIAP_DIAMBIL', 'version' => 2]);
        $this->assertBlocked($a);
        $this->assertBlocked($b);
        DB::commit();
        $this->workerResult($a, 200);
        $this->workerResult($b, 200);
        $this->assertSame(1, DB::table('notification_logs')->where('business_id', $business->id)
            ->where('kanal', 'whatsapp')->where('status', 'tertunda')->count());
        $this->assertSame(1, DB::table('notification_logs')->where('business_id', $business->id)
            ->where('kanal', 'whatsapp')->where('status', 'dilewati_batas')->count());
        $this->assertSame(2, DB::table('notification_logs')->where('business_id', $business->id)
            ->where('kanal', 'email')->where('status', 'tertunda')->count());
    }
}

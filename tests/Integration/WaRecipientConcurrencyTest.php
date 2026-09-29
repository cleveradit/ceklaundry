<?php

namespace Tests\Integration;

use App\Services\CustomerService;
use Illuminate\Support\Facades\DB;
use Tests\ConcurrentTestCase;

class WaRecipientConcurrencyTest extends ConcurrentTestCase
{
    public function test_customer_edit_commits_before_marker_so_old_worker_uses_new_number(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $business->forceFill(['wa_enabled' => true, 'wa_provider' => 'fonnte', 'wa_token' => 'test-token',
            'wa_sender_number' => '6281234567890'])->save();
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
        ]);
        $tx = DB::table('transactions')->find($id);
        $phone = DB::table('customers')->find($tx->customer_id)->no_hp;
        $log = $this->notification($business, $id, ['kanal' => 'whatsapp', 'tujuan' => $phone,
            'wa_quota_month' => now('Asia/Jakarta')->startOfMonth()->toDateString()]);
        $capture = tempnam(sys_get_temp_dir(), 'recipient-');
        try {
            DB::beginTransaction();
            DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
            $worker = $this->startWorker(['actor' => $owner->id, 'operation' => 'send-notification-capture',
                'business' => $business->id, 'log' => $log, 'capture' => $capture]);
            $this->assertBlocked($worker);
            app(CustomerService::class)->save($owner, ['nama' => 'Pelanggan Uji', 'no_hp' => '081234567890'], $tx->customer_id);
            DB::commit();
            $this->workerResult($worker, 200);
            $this->assertSame('6281234567890', file_get_contents($capture));
            $this->assertSame('berhasil', DB::table('notification_logs')->find($log)->status);
        } finally {
            @unlink($capture);
        }
    }
}

<?php

namespace Tests\Integration;

use App\Services\TransactionEmailVerificationService;
use App\Services\TransactionStateMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\ConcurrentTestCase;

class ReceiptEmailVerificationConcurrencyTest extends ConcurrentTestCase
{
    public function test_ready_commit_before_confirmation_rejects_link_and_uses_old_email(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'kode_resi' => 'ABC239', 'status' => 'DIPROSES', 'version' => 2,
            'notification_email' => 'lama@example.test',
        ]);
        $request = Request::create('/t/ABC239/email', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);
        app(TransactionEmailVerificationService::class)->request('ABC239', 'baru@example.test', $request);
        $tx = DB::table('transactions')->find($id);
        DB::beginTransaction();
        DB::table('businesses')->where('id', $business->id)->lockForUpdate()->first();
        $confirm = $this->startWorker(['actor' => $owner->id, 'operation' => 'email-confirm',
            'code' => 'ABC239', 'version' => $tx->email_verification_version]);
        $this->assertBlocked($confirm);
        app(TransactionStateMachine::class)->move($owner, $id, 'SIAP_DIAMBIL', $tx->version);
        DB::commit();
        $this->workerResult($confirm, 422);
        $result = DB::table('transactions')->find($id);
        $this->assertSame('SIAP_DIAMBIL', $result->status);
        $this->assertSame('lama@example.test', $result->notification_email);
        $this->assertNull($result->pending_notification_email);
        $this->assertSame('lama@example.test', DB::table('notification_logs')->where('transaction_id', $id)
            ->where('tipe', 'siap_diambil')->value('tujuan'));
    }
}

<?php

namespace Tests\Feature\Notification;

use App\Models\Business;
use App\Services\NotificationDispatcher;
use App\Services\OutboundGuard;
use App\Services\ReminderScheduler;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\FoundationTestCase;

class RestoreHoldTest extends FoundationTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_hold_reconciliation_revokes_pending_and_old_queue_job(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
            'notification_email' => 'pelanggan@example.test',
        ]);
        $this->inTenant($business, fn () => DB::transaction(function () use ($business, $id) {
            $locked = Business::query()->lockForUpdate()->findOrFail($business->id);
            app(NotificationDispatcher::class)->reserve($locked, DB::table('transactions')->find($id), 'siap_diambil', 'email');
        }));
        $this->assertSame(1, DB::table('jobs')->where('payload->business_id', $business->id)->count());
        config()->set('outbound.hold', true);
        $this->assertSame(0, Artisan::call('app:reconcile-notification-restore'));
        $log = DB::table('notification_logs')->where('transaction_id', $id)->first();
        $this->assertSame('perlu_pemeriksaan', $log->status);
        $this->assertSame('restore_hold', $log->reason_code);
        $this->assertSame(0, DB::table('jobs')->where('payload->business_id', $business->id)->count());
        config()->set('outbound.hold', false);
    }

    public function test_cutoff_excludes_same_second_and_historical_ready(): void
    {
        Carbon::setTestNow(now('Asia/Jakarta')->startOfSecond());
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $ready = now('Asia/Jakarta')->subDays(3);
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => $ready,
            'notification_email' => 'pelanggan@example.test',
        ]);
        config()->set('outbound.resume_at', now('Asia/Jakarta')->subHour()->format('Y-m-d H:i:s'));
        app(ReminderScheduler::class)->run();
        $this->assertSame(0, DB::table('transactions')->find($id)->reminder_count);
        $this->assertSame(0, DB::table('notification_logs')->where('transaction_id', $id)->count());
        $at = now('Asia/Jakarta')->format('Y-m-d H:i:s');
        config()->set('outbound.resume_at', $at);
        $this->assertFalse(app(OutboundGuard::class)->allows($business, now('Asia/Jakarta')));
        config()->set('outbound.resume_at', null);
        Carbon::setTestNow();
    }
}

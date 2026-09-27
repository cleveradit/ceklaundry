<?php

namespace Tests\Integration;

use App\Services\LifecycleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\FoundationTestCase;

class LifecycleTest extends FoundationTestCase
{
    public function test_wib_calendar_boundaries_and_priority(): void
    {
        [$business] = $this->tenant();
        $business->forceFill(['active_until' => '2026-09-28'])->save();
        $service = app(LifecycleService::class);
        foreach (['2026-09-21 00:00:00' => 'AKTIF', '2026-09-28 23:59:59' => 'AKTIF', '2026-09-29 00:00:00' => 'TENGGANG', '2026-10-05 23:59:59' => 'TENGGANG', '2026-10-06 00:00:00' => 'BACA_SAJA'] as $time => $status) {
            $this->travelTo(Carbon::parse($time, 'Asia/Jakarta'));
            $this->assertSame($status, $service->status($business));
            $this->assertTrue($service->warning($business));
        }
        $business->forceFill(['is_active' => false]);
        $this->assertSame('NONAKTIF', $service->status($business));
        $this->assertTrue($service->publicAllowed($business));
        $business->forceFill(['is_active' => true, 'is_demo' => true, 'demo_expires_at' => now()]);
        $this->assertSame('DEMO_EXPIRED', $service->status($business));
        $this->assertFalse($service->publicAllowed($business));
        $this->travelBack();
    }

    public function test_off_on_closes_pending_but_preserves_in_flight_and_audits(): void
    {
        $developer = $this->developer();
        [$business, $owner] = $this->tenant($developer);
        $branch = $this->branch($owner);
        $tx = $this->transaction($business, $owner, $branch->id);
        $pending = $this->notification($business, $tx);
        $inflight = $this->notification($business, $tx, ['status' => 'diproses', 'processing_token' => (string) Str::uuid(), 'processing_started_at' => now(), 'delivery_started_at' => now(), 'attempt_count' => 1]);
        $service = app(LifecycleService::class);
        $service->update($developer, $business->id, ['active_until' => now()->toDateString(), 'is_active' => false]);
        $service->update($developer, $business->id, ['active_until' => now()->addMonth()->toDateString(), 'is_active' => true]);
        $this->assertSame('dilewati_kondisi', DB::table('notification_logs')->where('id', $pending)->value('status'));
        $this->assertSame('diproses', DB::table('notification_logs')->where('id', $inflight)->value('status'));
        $this->assertSame(2, DB::table('audit_logs')->count());
    }

    public function test_extension_from_read_only_and_scanner_invalidate_old_pending(): void
    {
        $developer = $this->developer();
        [$business, $owner] = $this->tenant($developer);
        $branch = $this->branch($owner);
        $tx = $this->transaction($business, $owner, $branch->id);
        $business->forceFill(['active_until' => now()->subDays(8)])->save();
        $id = $this->notification($business, $tx);
        app(LifecycleService::class)->update($developer, $business->id, ['active_until' => now()->addMonth()->toDateString(), 'is_active' => true]);
        $this->assertSame('dilewati_kondisi', DB::table('notification_logs')->where('id', $id)->value('status'));
        $business->refresh()->forceFill(['active_until' => now()->subDays(8)])->save();
        $id = $this->notification($business, $tx);
        $this->artisan('app:expire-business-access')->assertSuccessful();
        $this->assertSame('dilewati_kondisi', DB::table('notification_logs')->where('id', $id)->value('status'));
    }
}

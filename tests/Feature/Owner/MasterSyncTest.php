<?php

namespace Tests\Feature\Owner;

use App\Models\AuditLog;
use App\Models\Service;
use App\Services\MasterSyncService;
use App\Services\ServiceCatalogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\FoundationTestCase;

class MasterSyncTest extends FoundationTestCase
{
    public function test_preview_add_update_reactivate_preserve_local_and_rename(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $catalog = app(ServiceCatalogService::class);
        $sync = app(MasterSyncService::class);
        $data = ['nama' => 'Cuci', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 24, 'is_active' => true];
        $master = $catalog->save($owner, $data);
        $catalog->save($owner, [...$data, 'nama' => 'Setrika']);
        $catalog->save($owner, [...$data, 'nama' => 'Nonaktif', 'is_active' => false]);
        $catalog->save($owner, [...$data, 'harga' => 5000, 'is_active' => false], null, $branch->id);
        $catalog->save($owner, [...$data, 'nama' => 'Khusus'], null, $branch->id);
        $preview = $sync->preview($owner, [$branch->id]);
        $this->assertSame(['aktifkan', 'tambah'], array_column($preview['branches'][0]['changes'], 'action'));
        $this->assertSame(['Khusus'], $preview['branches'][0]['untouched']);
        $sync->apply($owner, [$branch->id], $preview['fingerprint']);
        $this->assertSame(3, DB::table('services')->count());
        $this->assertSame(7000, DB::table('services')->where('nama', 'Cuci')->value('harga'));
        $this->assertSame(1, DB::table('services')->where('nama', 'Cuci')->value('is_active'));
        $catalog->save($owner, [...$data, 'nama' => 'Cuci Baru'], $master->id);
        $preview = $sync->preview($owner, [$branch->id]);
        $sync->apply($owner, [$branch->id], $preview['fingerprint']);
        $this->assertSame(4, DB::table('services')->count());
        $this->assertTrue(DB::table('services')->where('nama', 'Cuci')->exists());
    }

    public function test_stale_preview_returns_409_without_partial_changes(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $catalog = app(ServiceCatalogService::class);
        $sync = app(MasterSyncService::class);
        $data = ['nama' => 'Cuci', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 24, 'is_active' => true];
        $master = $catalog->save($owner, $data);
        $preview = $sync->preview($owner, [$branch->id]);
        $catalog->save($owner, [...$data, 'harga' => 9000], $master->id);
        $this->actingAs($owner)->post('/owner/sync', ['branches' => [$branch->id], 'fingerprint' => $preview['fingerprint'], 'confirmed' => true])->assertStatus(409);
        $this->assertSame(0, DB::table('services')->count());
        $this->assertSame(0, DB::table('audit_logs')->count());
    }

    public function test_failure_in_second_branch_or_audit_rolls_back_all_branches(): void
    {
        [$business, $owner] = $this->tenant();
        $one = $this->branch($owner);
        $two = $this->branch($owner, 'Kedua');
        app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 24, 'is_active' => true]);
        $sync = app(MasterSyncService::class);
        $ids = [$one->id, $two->id];
        $preview = $sync->preview($owner, $ids);
        Event::listen('eloquent.created: '.Service::class, function (Service $service) use ($two) {
            if ($service->branch_id === $two->id) {
                throw new \RuntimeException('second branch fault');
            }
        });
        try {
            $sync->apply($owner, $ids, $preview['fingerprint']);
            $this->fail();
        } catch (\RuntimeException) {
        }
        $this->assertSame(0, DB::table('services')->count());
        $this->assertSame(0, DB::table('audit_logs')->count());
        Event::forget('eloquent.created: '.Service::class);
        Event::listen('eloquent.creating: '.AuditLog::class, fn () => throw new \RuntimeException('audit fault'));
        try {
            $sync->apply($owner, $ids, $preview['fingerprint']);
            $this->fail();
        } catch (\RuntimeException) {
        }
        $this->assertSame(0, DB::table('services')->count());
    }
}

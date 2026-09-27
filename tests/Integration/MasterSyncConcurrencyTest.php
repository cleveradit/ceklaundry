<?php

namespace Tests\Integration;

use App\Models\Business;
use App\Services\MasterSyncService;
use App\Services\ServiceCatalogService;
use Illuminate\Support\Facades\DB;
use Tests\ConcurrentTestCase;

class MasterSyncConcurrencyTest extends ConcurrentTestCase
{
    public function test_two_simultaneous_applies_do_not_duplicate_and_second_is_stale(): void
    {
        [$business,$owner] = $this->tenant();
        $branch = $this->branch($owner);
        app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 24, 'is_active' => true]);
        $sync = app(MasterSyncService::class);
        $preview = $sync->preview($owner, [$branch->id]);
        DB::beginTransaction();
        Business::lockForUpdate()->findOrFail($business->id);
        $child = $this->startWorker(['operation' => 'sync', 'actor' => $owner->id, 'branches' => [$branch->id], 'fingerprint' => $preview['fingerprint']]);
        $this->assertBlocked($child);
        $sync->apply($owner, [$branch->id], $preview['fingerprint']);
        DB::commit();
        $this->workerResult($child, 409);
        $this->assertSame(1, DB::table('services')->where('branch_id', $branch->id)->count());
        $this->assertSame(1, DB::table('audit_logs')->where('business_id', $business->id)->count());
    }
}

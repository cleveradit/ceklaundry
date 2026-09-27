<?php

namespace Tests\Integration;

use App\Models\Business;
use App\Services\AccountService;
use Illuminate\Support\Facades\DB;
use Tests\ConcurrentTestCase;

class BranchAdminConcurrencyTest extends ConcurrentTestCase
{
    public function test_deactivation_waits_for_writer_then_sees_active_transaction(): void
    {
        [$business,$owner] = $this->tenant();
        $branch = $this->branch($owner);
        DB::beginTransaction();
        Business::lockForUpdate()->findOrFail($business->id);
        $child = $this->startWorker(['operation' => 'deactivate', 'actor' => $owner->id, 'branch' => $branch->id]);
        $this->assertBlocked($child);
        $this->transaction($business, $owner, $branch->id);
        DB::commit();
        $this->workerResult($child, 422);
        $this->assertSame(1, DB::table('branches')->where('id', $branch->id)->value('is_active'));
    }

    public function test_admin_assignment_is_reread_after_waiting_for_root(): void
    {
        [$business,$owner] = $this->tenant();
        $one = $this->branch($owner);
        $two = $this->branch($owner, 'Dua');
        $admin = app(AccountService::class)->saveAdmin($owner, ['nama' => 'Admin', 'email' => 'race@example.test', 'password' => $this->password(), 'branch_id' => $one->id, 'is_active' => true]);
        $admin->forceFill(['must_change_password' => false])->save();
        DB::beginTransaction();
        Business::lockForUpdate()->findOrFail($business->id);
        $child = $this->startWorker(['operation' => 'admin-write', 'actor' => $admin->id, 'branch' => $one->id]);
        $this->assertBlocked($child);
        $admin->forceFill(['branch_id' => $two->id])->save();
        DB::commit();
        $this->workerResult($child, 404);
        $this->assertNotSame('ILLEGAL', DB::table('branches')->where('id', $one->id)->value('alamat'));
    }
}

<?php

namespace Tests\Feature\Owner;

use App\Models\Branch;
use App\Models\User;
use App\Services\AccountService;
use App\Services\BranchService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\FoundationTestCase;

class BranchAdminTest extends FoundationTestCase
{
    public function test_active_transactions_block_deactivation_and_completed_history_is_kept(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id);
        $data = ['nama' => 'Cabang diperbarui', 'alamat' => 'Alamat', 'telepon' => '+62 (812) 3456-7890', 'is_active' => false];
        $this->actingAs($owner)->put('/owner/branches/'.$branch->id, $data)->assertSessionHasErrors('is_active');
        DB::table('transactions')->where('id', $id)->update(['status' => 'DIBATALKAN', 'alasan_pembatalan' => 'Uji']);
        $saved = app(BranchService::class)->save($owner, $data, $branch->id);
        $this->assertFalse($saved->is_active);
        $this->assertSame('6281234567890', $saved->telepon);
        $this->assertTrue(DB::table('transactions')->where('id', $id)->exists());
        $saved = app(BranchService::class)->save($owner, [...$data, 'is_active' => true], $branch->id);
        $this->assertTrue($saved->is_active);
    }

    public function test_multiple_admins_reassignment_email_revocation_and_safe_reset(): void
    {
        [$business, $owner] = $this->tenant();
        $one = $this->branch($owner);
        $two = $this->branch($owner, 'Kedua');
        $accounts = app(AccountService::class);
        $data = ['nama' => 'Admin', 'email' => 'admin1@example.test', 'password' => $this->password(), 'branch_id' => $one->id, 'is_active' => true];
        $admin = $accounts->saveAdmin($owner, $data);
        $accounts->saveAdmin($owner, [...$data, 'email' => 'admin2@example.test']);
        $this->assertSame(2, User::where('branch_id', $one->id)->count());
        $admin->forceFill(['must_change_password' => false])->save();
        $this->actingAs($admin)->get('/app')->assertOk();
        $saved = $accounts->saveAdmin($owner, [...$data, 'branch_id' => $two->id, 'business_id' => 99999, 'role' => 'developer'], $admin->id);
        $this->assertSame($business->id, $saved->business_id);
        $this->assertSame('admin', $saved->role);
        $this->get('/app')->assertOk()->assertSee('Kedua');
        DB::table('sessions')->insert(['id' => 'old', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()]);
        DB::table('password_reset_tokens')->insert(['email' => $admin->email, 'token' => Hash::make('fixture'), 'created_at' => now()]);
        $accounts->saveAdmin($owner, [...$data, 'branch_id' => $two->id, 'email' => 'changed@example.test'], $admin->id);
        $this->assertSame(0, DB::table('sessions')->count());
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
        $new = $this->password();
        $accounts->operatorReset($owner, $admin->id, $new);
        $this->assertTrue($admin->fresh()->must_change_password);
        $this->assertStringNotContainsString($new, DB::table('audit_logs')->first()->detail);
        $accounts->saveAdmin($owner, [...$data, 'is_active' => false], $admin->id);
        $this->get('/app')->assertForbidden();
    }

    public function test_edit_form_cannot_silently_change_password(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $accounts = app(AccountService::class);
        $data = ['nama' => 'Admin', 'email' => 'operator@example.test', 'password' => $this->password(), 'branch_id' => $branch->id, 'is_active' => true];
        $admin = $accounts->saveAdmin($owner, $data);
        $hash = $admin->password;
        $accounts->saveAdmin($owner, [...$data, 'password' => $this->password()], $admin->id);
        $this->assertSame($hash, $admin->fresh()->password);
    }

    public function test_phone_boundaries_reject_invalid_digits(): void
    {
        [$business, $owner] = $this->tenant();
        foreach (['62012345678', '628123', '62812345678901234'] as $phone) {
            $this->actingAs($owner)->post('/owner/branches', ['nama' => 'Cabang', 'alamat' => 'Alamat', 'telepon' => $phone, 'is_active' => true])->assertSessionHasErrors('telepon');
        }
        $this->assertSame(0, $this->inTenant($business, fn () => Branch::count()));
    }
}

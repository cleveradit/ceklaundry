<?php

namespace Tests\Feature\Developer;

use App\Models\User;
use App\Services\DeveloperBusinessSummary;
use App\Services\TenantProvisioner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\FoundationTestCase;

class BusinessManagementTest extends FoundationTestCase
{
    public function test_provision_defaults_and_duplicate_owner_rollback(): void
    {
        $developer = $this->developer();
        [$business, $owner] = $this->tenant($developer);
        $this->assertSame(1, DB::table('business_settings')->count());
        $this->assertSame(1, DB::table('loyalty_settings')->count());
        $this->actingAs($developer)->post('/dev/businesses', ['nama' => 'Duplikat', 'active_until' => now()->toDateString(), 'owner' => ['nama' => 'Owner', 'email' => $owner->email, 'password' => $this->password()]])->assertSessionHasErrors('email');
        $this->assertSame(1, DB::table('businesses')->count());
    }

    public function test_provision_failure_after_owner_rolls_back_all_rows(): void
    {
        $developer = $this->developer();
        Event::listen('eloquent.created: '.User::class, function (User $user) {
            if ($user->role === 'owner') {
                throw new \RuntimeException('fault after owner');
            }
        });
        try {
            app(TenantProvisioner::class)->provision($developer, ['nama' => 'Fault', 'active_until' => now()->toDateString(), 'owner' => ['nama' => 'Owner', 'email' => 'owner@example.test', 'password' => $this->password()]]);
            $this->fail();
        } catch (\RuntimeException) {
        }
        $this->assertSame(0, DB::table('businesses')->count());
        $this->assertSame(1, User::count());
        $this->assertSame(0, DB::table('business_settings')->count());
    }

    public function test_statistics_include_boundary_cancelled_and_inactive_branches_without_rows(): void
    {
        $this->travelTo(now()->startOfSecond());
        $developer = $this->developer();
        [$business, $owner] = $this->tenant($developer);
        $branch = $this->branch($owner);
        DB::table('branches')->where('id', $branch->id)->update(['is_active' => false]);
        $this->transaction($business, $owner, $branch->id, ['status' => 'DIBATALKAN', 'alasan_pembatalan' => 'Uji', 'waktu_masuk' => now()->subDays(30)]);
        $this->transaction($business, $owner, $branch->id, ['waktu_masuk' => now()->subDays(30)->subSecond()]);
        $result = app(DeveloperBusinessSummary::class)->list($developer);
        $this->assertSame(1, $result[0]['transaction_count']);
        $this->assertSame(1, $result[0]['branch_count']);
        $this->assertSame(['id', 'nama', 'active_until', 'is_active', 'status', 'branch_count', 'transaction_count'], array_keys($result[0]));
        $this->actingAs($developer)->get('/dev/transactions')->assertNotFound();
        $this->travelBack();
    }
}

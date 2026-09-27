<?php

namespace Tests\Feature\Foundation;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\FoundationTestCase;

class SchemaContractTest extends FoundationTestCase
{
    public function test_migrations_defaults_and_sensitive_casts(): void
    {
        [$business] = $this->tenant();
        $row = DB::table('loyalty_settings')->where('business_id', $business->id)->first();
        $this->assertSame(0, $row->is_active);
        $this->assertSame(10, $row->stempel_dibutuhkan);
        $this->assertNull($row->master_service_id);
        $this->assertNull($row->berat_maks_gratis);
        $secret = bin2hex(random_bytes(16));
        $business->forceFill(['wa_token' => $secret, 'smtp_config' => ['password' => $secret]])->save();
        $this->assertNotContains($secret, array_values($business->toArray()));
        $this->assertNotSame($secret, DB::table('businesses')->where('id', $business->id)->value('wa_token'));
        $this->assertSame($secret, Business::find($business->id)->wa_token);
        foreach (['jobs', 'failed_jobs', 'job_batches', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'audit_logs', 'notification_logs', 'transaction_items'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        $this->assertFalse(Schema::hasColumn('audit_logs', 'updated_at'));
        $this->assertFalse(Schema::hasColumn('users', 'remember_token'));
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
    }

    public function test_second_owner_is_rejected_by_database(): void
    {
        [$business] = $this->tenant();
        $this->expectException(QueryException::class);
        User::factory()->create(['role' => 'owner', 'business_id' => $business->id]);
    }

    public function test_admin_without_branch_is_rejected(): void
    {
        [$business] = $this->tenant();
        $this->expectException(QueryException::class);
        User::factory()->create(['role' => 'admin', 'business_id' => $business->id]);
    }

    public function test_cross_tenant_branch_is_rejected_by_composite_fk(): void
    {
        [$a] = $this->tenant();
        [$b, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $this->expectException(QueryException::class);
        User::factory()->create(['role' => 'admin', 'business_id' => $a->id, 'branch_id' => $branch->id]);
    }

    public function test_zero_reminder_rejected_even_when_disabled(): void
    {
        [$a] = $this->tenant();
        $this->expectException(QueryException::class);
        DB::table('business_settings')->where('business_id', $a->id)->update(['reminder_enabled' => false, 'reminder_first_days' => 0]);
    }

    public function test_parent_with_history_cannot_be_deleted(): void
    {
        [$a, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $this->transaction($a, $owner, $branch->id);
        $this->expectException(QueryException::class);
        DB::table('branches')->where('id', $branch->id)->delete();
    }
}

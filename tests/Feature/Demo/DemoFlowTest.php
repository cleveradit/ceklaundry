<?php

namespace Tests\Feature\Demo;

use App\Models\Business;
use App\Models\User;
use App\Services\DemoProvisioner;
use App\Services\DemoPurgeService;
use Database\Seeders\DemoFixtureSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\FoundationTestCase;

class DemoFlowTest extends FoundationTestCase
{
    public function test_one_click_provisions_exact_fixture_and_role_switch_stays_in_tenant(): void
    {
        Http::fake();
        Mail::fake();
        $this->post('/demo')->assertRedirect('/owner');
        $business = Business::query()->where('is_demo', true)->firstOrFail();
        $this->assertSame(2, DB::table('branches')->where('business_id', $business->id)->count());
        $this->assertSame(3, DB::table('master_services')->where('business_id', $business->id)->count());
        $this->assertSame(6, DB::table('services')->where('business_id', $business->id)->count());
        $this->assertSame(6, DB::table('customers')->where('business_id', $business->id)->count());
        $this->assertSame(15, DB::table('transactions')->where('business_id', $business->id)->count());
        $this->assertSame(10, DB::table('transactions')->where('business_id', $business->id)->where('status', 'SUDAH_DIAMBIL')->count());
        $first = DB::table('customers')->where('business_id', $business->id)->orderBy('id')->first();
        $this->assertSame(10, (int) $first->stamp_count);
        $this->assertSame(10, (int) DB::table('loyalty_histories')->where('business_id', $business->id)->where('customer_id', $first->id)->sum('jumlah'));
        $this->assertSame(1, DB::table('promos')->where('business_id', $business->id)->where('is_active', true)->count());
        $this->assertSame(0, DB::table('notification_logs')->where('business_id', $business->id)->where('status', 'berhasil')->count());
        $this->assertGreaterThanOrEqual(24, DB::table('notification_logs')->where('business_id', $business->id)->where('status', 'ditekan_demo')->count());
        Http::assertNothingSent();
        Mail::assertNothingSent();

        $this->get('/owner')->assertOk();
        $before = $this->app['session.store']->getId();
        $this->post('/demo/role')->assertRedirect('/app');
        $this->assertNotSame($before, $this->app['session.store']->getId());
        $this->get('/app')->assertOk();
        $this->get('/owner')->assertForbidden();
        $this->post('/demo/role', ['user_id' => 1])->assertStatus(422);
        $this->post('/demo/role')->assertRedirect('/owner');
    }

    public function test_expiry_rejects_requests_before_purge_and_purge_spares_normal_tenant(): void
    {
        [$normal] = $this->tenant();
        $this->post('/demo')->assertRedirect('/owner');
        $demo = Business::query()->where('is_demo', true)->firstOrFail();
        $code = DB::table('transactions')->where('business_id', $demo->id)->value('kode_resi');
        $cacheKey = 'demo:business:'.$demo->id.':probe';
        Cache::put($cacheKey, 'demo', 3600);
        DB::table('jobs')->insert([
            'queue' => 'default', 'payload' => json_encode(['business_id' => $demo->id]),
            'attempts' => 0, 'available_at' => time(), 'created_at' => time(),
        ]);
        $this->travelTo($demo->demo_expires_at);
        $this->get('/owner')->assertStatus(410);
        $this->get('/t/'.$code)->assertStatus(410);
        $this->assertTrue(app(DemoPurgeService::class)->purge($demo->id));
        $this->assertFalse(app(DemoPurgeService::class)->purge($demo->id));
        $this->assertSame(0, DB::table('transactions')->where('business_id', $demo->id)->count());
        $this->assertSame(0, DB::table('jobs')->where('payload->business_id', $demo->id)->count());
        $this->assertNull(Cache::get($cacheKey));
        $this->assertFalse(DB::table('businesses')->where('id', $demo->id)->exists());
        $this->assertTrue(DB::table('businesses')->where('id', $normal->id)->exists());
        $this->travelBack();
    }

    public function test_demo_rate_limit_is_three_per_ip_per_wib_day(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.71']);
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/demo')->assertRedirect('/owner');
            $this->post('/logout')->assertRedirect('/login');
        }
        $this->post('/demo')->assertStatus(429)->assertSee('Batas tiga demo per hari tercapai');
        $this->assertSame(3, Business::query()->where('is_demo', true)->count());
        $this->travelTo(now('Asia/Jakarta')->addDay()->startOfDay());
        $this->post('/demo')->assertRedirect('/owner');
        $this->travelBack();
    }

    public function test_failed_fixture_rolls_back_business_and_demo_users(): void
    {
        $seeder = \Mockery::mock(DemoFixtureSeeder::class);
        $seeder->shouldReceive('seed')->once()->andThrow(new \RuntimeException('fixture gagal'));
        $this->app->instance(DemoFixtureSeeder::class, $seeder);
        $this->post('/demo')->assertStatus(500);
        $this->assertSame(0, Business::query()->where('is_demo', true)->count());
        $this->assertSame(0, User::query()->where('email', 'like', 'demo-%@example.invalid')->count());
        $this->assertGuest();
    }

    public function test_role_switch_is_demo_only_and_reserved_pair_cannot_be_changed(): void
    {
        [, $normalOwner] = $this->tenant();
        $this->actingAs($normalOwner)->post('/demo/role')->assertForbidden();
        $this->post('/logout')->assertRedirect('/login');
        $this->post('/demo')->assertRedirect('/owner');
        $demo = Business::query()->where('is_demo', true)->firstOrFail();
        $admin = User::query()->where('business_id', $demo->id)->where('role', 'admin')->firstOrFail();
        $this->put('/owner/admins/'.$admin->id, [
            'nama' => 'Admin Baru', 'email' => 'lain@example.invalid', 'branch_id' => $admin->branch_id, 'is_active' => false,
        ])->assertForbidden();
        $this->post('/owner/admins/'.$admin->id.'/reset', ['password' => $this->password()])->assertForbidden();
        $this->put('/owner/branches/'.$admin->branch_id, [
            'nama' => 'Cabang Utama', 'alamat' => 'Jalan Uji', 'telepon' => '081234567890', 'is_active' => false,
        ])->assertForbidden();
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_changed_reserved_assignment_invalidates_demo_session(): void
    {
        $this->post('/demo')->assertRedirect('/owner');
        $demo = Business::query()->where('is_demo', true)->firstOrFail();
        $admin = User::query()->where('business_id', $demo->id)->where('role', 'admin')->firstOrFail();
        $secondBranch = DB::table('branches')->where('business_id', $demo->id)->orderByDesc('id')->value('id');
        DB::table('users')->where('id', $admin->id)->update(['branch_id' => $secondBranch]);
        $this->get('/owner')->assertForbidden();
    }

    public function test_purge_run_catches_expired_backlog_after_scheduler_resumes(): void
    {
        $provisioner = app(DemoProvisioner::class);
        $expired = [$provisioner->provision()['business']->id, $provisioner->provision()['business']->id];
        $live = $provisioner->provision()['business']->id;
        DB::table('businesses')->whereIn('id', $expired)->update(['demo_expires_at' => now()->subMinute()]);
        $this->assertSame(2, app(DemoPurgeService::class)->run());
        $this->assertSame(0, app(DemoPurgeService::class)->run());
        $this->assertTrue(DB::table('businesses')->where('id', $live)->exists());
    }

    public function test_manual_verification_and_account_paths_never_send_from_demo(): void
    {
        Http::fake();
        Mail::fake();
        $this->post('/demo')->assertRedirect('/owner');
        $demo = Business::query()->where('is_demo', true)->firstOrFail();
        $ready = DB::table('transactions')->where('business_id', $demo->id)->where('status', 'SIAP_DIAMBIL')->orderBy('id')->first();
        $accepted = DB::table('transactions')->where('business_id', $demo->id)->where('status', 'DITERIMA')->first();
        $this->post('/app/transactions/'.$ready->id.'/notifications/email', ['request_key' => (string) Str::uuid()])->assertRedirect();
        $this->post('/app/transactions/'.$ready->id.'/notifications/whatsapp', [
            'type' => 'pengingat', 'request_key' => (string) Str::uuid(),
        ])->assertOk()->assertSee('Pratinjau pesan demo')->assertDontSee('wa.me');
        $this->get('/t/'.$ready->kode_resi)->assertOk()->assertDontSee('href="tel:', false);
        $oldEmail = $accepted->notification_email;
        $this->post('/t/'.$accepted->kode_resi.'/email', ['email' => 'baru@example.invalid'])->assertRedirect();
        $this->assertSame($oldEmail, DB::table('transactions')->where('id', $accepted->id)->value('notification_email'));
        $this->assertSame('ditekan_demo', DB::table('notification_logs')->where('transaction_id', $accepted->id)->where('tipe', 'verifikasi_email')->value('status'));
        $this->post('/forgot-password', ['email' => 'demo-owner-'.$demo->id.'@example.invalid'])->assertRedirect();
        $this->assertFalse(DB::table('password_reset_tokens')->where('email', 'demo-owner-'.$demo->id.'@example.invalid')->exists());
        $this->assertSame(0, DB::table('notification_logs')->where('business_id', $demo->id)->where('status', 'berhasil')->count());
        $this->assertSame(0, DB::table('jobs')->where('payload->business_id', $demo->id)->count());
        Http::assertNothingSent();
        Mail::assertNothingSent();
    }
}

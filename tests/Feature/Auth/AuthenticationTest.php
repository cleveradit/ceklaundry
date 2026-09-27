<?php

namespace Tests\Feature\Auth;

use App\Services\AccountService;
use App\Services\AuthRateLimiter;
use App\Support\Input;
use Database\Seeders\DeveloperSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\FoundationTestCase;

class AuthenticationTest extends FoundationTestCase
{
    public function test_login_forces_password_change_and_has_no_signup(): void
    {
        [$business, $owner, $password] = $this->tenant();
        $owner->forceFill(['must_change_password' => true])->save();
        $this->post('/login', ['email' => strtoupper($owner->email), 'password' => $password])->assertRedirect('/password/change');
        $this->get('/owner')->assertRedirect('/password/change');
        $this->get('/')->assertOk();
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
        $next = $this->password();
        $this->post('/password/change', ['current_password' => $password, 'password' => $next, 'password_confirmation' => $next])->assertRedirect('/owner');
        $this->assertFalse($owner->fresh()->must_change_password);
        $this->assertTrue(Hash::check($next, $owner->fresh()->password));
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_initial_password_change_works_in_read_only_mode(): void
    {
        [$business, $owner, $password] = $this->tenant();
        $business->forceFill(['active_until' => now()->subDays(8)->toDateString()])->save();
        $owner->forceFill(['must_change_password' => true])->save();
        $new = $this->password();
        $this->actingAs($owner)->post('/password/change', ['current_password' => $password, 'password' => $new, 'password_confirmation' => $new])->assertRedirect('/owner');
        $this->get('/owner')->assertOk();
        $this->post('/owner/branches', [])->assertStatus(423);
    }

    public function test_bcrypt_character_and_byte_boundaries(): void
    {
        foreach ([str_repeat('a', 11) => false, str_repeat('a', 12) => true, str_repeat('a', 72) => true, str_repeat('a', 73) => false, str_repeat('é', 36) => true, str_repeat('é', 37) => false] as $password => $valid) {
            $this->assertSame($valid, Validator::make(['password' => $password], ['password' => Input::passwordRules()])->passes());
        }
    }

    public function test_inactive_account_or_business_blocks_old_session_but_not_logout(): void
    {
        [$business, $owner] = $this->tenant();
        $this->actingAs($owner)->get('/owner')->assertOk();
        $business->forceFill(['is_active' => false])->save();
        $this->get('/owner')->assertForbidden();
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_password_change_revokes_other_sessions_and_tokens(): void
    {
        [$business, $owner, $password] = $this->tenant();
        foreach (['current', 'other'] as $id) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $owner->id, 'payload' => '', 'last_activity' => time()]);
        }
        DB::table('password_reset_tokens')->insert(['email' => $owner->email, 'token' => Hash::make('fixture'), 'created_at' => now()]);
        app(AccountService::class)->changePassword($owner, $password, $this->password(), 'current');
        $this->assertSame(['current'], DB::table('sessions')->pluck('id')->all());
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    public function test_login_limit_is_five_per_identity_and_resets_at_window_boundary(): void
    {
        $this->travelTo(now()->startOfMinute());
        $limiter = app(AuthRateLimiter::class);
        for ($i = 0; $i < 5; $i++) {
            $limiter->attempt('login', 'a@example.test', '192.0.2.1');
        }
        try {
            $limiter->attempt('login', 'a@example.test', '192.0.2.1');
            $this->fail('Expected limit');
        } catch (HttpException $e) {
            $this->assertSame(429, $e->getStatusCode());
        }
        $this->travel(1)->minutes();
        $limiter->attempt('login', 'a@example.test', '192.0.2.1');
        $this->travelBack();
    }

    public function test_bootstrap_seeder_requires_secret_injection(): void
    {
        $this->expectException(\RuntimeException::class);
        (new DeveloperSeeder)->run();
    }

    public function test_inertia_redirect_without_referer_and_logout_clears_history(): void
    {
        [, $owner] = $this->tenant();
        $version = $this->get('/login')->viewData('page')['version'];
        $this->actingAs($owner)->get('/owner/branches', ['X-Inertia' => 'true', 'X-Inertia-Version' => $version, 'X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertHeader('Referrer-Policy', 'no-referrer');
        $this->post('/owner/branches', ['nama' => 'Return test', 'alamat' => 'Jalan 1', 'telepon' => '081234567890', 'is_active' => true])->assertRedirect('/owner/branches');
        $this->post('/logout')->assertRedirect('/login')->assertSessionHas('inertia.clear_history', true);
    }

    public function test_reset_and_ip_limits_and_duplicate_bootstrap(): void
    {
        $limiter = app(AuthRateLimiter::class);
        for ($i = 0; $i < 30; $i++) {
            $limiter->attempt('login', 'person'.$i.'@example.test', '192.0.2.90');
        }
        try {
            $limiter->attempt('login', 'last@example.test', '192.0.2.90');
            $this->fail('IP limit');
        } catch (HttpException $e) {
            $this->assertSame(429, $e->getStatusCode());
        }
        for ($i = 0; $i < 3; $i++) {
            $limiter->attempt('reset', 'reset@example.test', '192.0.2.91');
        }
        try {
            $limiter->attempt('reset', 'reset@example.test', '192.0.2.92');
            $this->fail('Identity limit');
        } catch (HttpException $e) {
            $this->assertSame(429, $e->getStatusCode());
        }
        for ($i = 0; $i < 10; $i++) {
            $limiter->attempt('reset', 'reset'.$i.'@example.test', '192.0.2.93');
        }
        try {
            $limiter->attempt('reset', 'last@example.test', '192.0.2.93');
            $this->fail('IP reset limit');
        } catch (HttpException $e) {
            $this->assertSame(429, $e->getStatusCode());
        }
        $dev = $this->developer();
        $hash = $dev->password;
        try {
            app(AccountService::class)->validateAccount(['nama' => 'Overwrite', 'email' => $dev->email, 'password' => $this->password()]);
            $this->fail('Duplicate accepted');
        } catch (ValidationException) {
        }
        $this->assertSame($hash, $dev->fresh()->password);
    }
}

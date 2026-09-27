<?php

namespace Tests\Integration;

use App\Services\AccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\FoundationTestCase;

class AuthResetQueueTest extends FoundationTestCase
{
    private function executeNext(): void
    {
        $job = Queue::connection('database')->pop();
        $this->assertNotNull($job);
        $job->fire();
        $job->delete();
    }

    public function test_reset_job_is_encrypted_latest_token_only_and_reset_single_use(): void
    {
        [$business, $owner] = $this->tenant();
        $accounts = app(AccountService::class);
        $accounts->requestReset($owner->email);
        $accounts->requestReset($owner->email);
        $payload = DB::table('jobs')->first()->payload;
        $this->assertStringNotContainsString($owner->email, $payload);
        $this->assertSame($business->id, json_decode($payload, true)['business_id']);
        $this->assertSame(0, DB::table('notification_logs')->count());
        $raw = '';
        Mail::shouldReceive('raw')->once()->andReturnUsing(function ($body) use (&$raw) {
            $raw = $body;
        });
        $this->executeNext();
        $this->assertSame('', $raw);
        $this->executeNext();
        $this->assertNotSame('', $raw);
        preg_match('~/reset-password/([A-Za-z0-9]+)~', $raw, $match);
        $this->assertNotEmpty($match[1]);
        $next = $this->password();
        $accounts->resetPassword($owner->email, $match[1], $next);
        $this->assertTrue(Hash::check($next, $owner->fresh()->password));
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
        $this->expectException(ValidationException::class);
        $accounts->resetPassword($owner->email, $match[1], $this->password());
    }

    public function test_hold_demo_cutoff_and_expired_jobs_cannot_send(): void
    {
        [$business, $owner] = $this->tenant();
        $accounts = app(AccountService::class);
        config(['outbound.hold' => true]);
        $accounts->requestReset($owner->email);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
        config(['outbound.hold' => false]);
        $business->forceFill(['is_demo' => true, 'demo_expires_at' => now()->addDays(7), 'active_until' => null])->save();
        $accounts->requestReset($owner->email);
        $this->assertSame(0, DB::table('jobs')->count());
        $business->forceFill(['is_demo' => false, 'demo_expires_at' => null, 'active_until' => now()->addDay()])->save();
        $accounts->requestReset($owner->email);
        config(['outbound.resume_at' => now()->toDateTimeString()]);
        Mail::shouldReceive('raw')->never();
        $this->executeNext();
        config(['outbound.resume_at' => null]);
        $accounts->requestReset($owner->email);
        $this->travel(60)->minutes();
        $this->executeNext();
        $this->travelBack();
    }

    public function test_token_and_queue_insert_rollback_together(): void
    {
        [$business, $owner] = $this->tenant();
        try {
            DB::transaction(function () use ($owner) {
                app(AccountService::class)->requestReset($owner->email);
                throw new \RuntimeException('fault');
            });
        } catch (\RuntimeException) {
        }
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    public function test_generic_reset_response_and_inactive_user_can_reset_without_activation(): void
    {
        [$business, $owner] = $this->tenant();
        $owner->forceFill(['is_active' => false])->save();
        $this->from('/forgot-password')->post('/forgot-password', ['email' => $owner->email])->assertRedirect('/forgot-password')->assertSessionHas('success');
        $this->assertFalse($owner->fresh()->is_active);
        $this->assertSame(1, DB::table('jobs')->count());
        $this->post('/forgot-password', ['email' => 'missing@example.test'])->assertSessionHas('success');
        $this->assertSame(1, DB::table('jobs')->count());
    }

    public function test_smtp_failure_is_sanitized_and_never_automatically_retried(): void
    {
        [, $owner] = $this->tenant();
        app(AccountService::class)->requestReset($owner->email);
        $failure = null;
        Queue::failing(function ($event) use (&$failure) {
            $failure = $event->exception;
        });
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('SMTP secret recipient '.$owner->email));
        $job = Queue::connection('database')->pop();
        $this->assertSame(1, $job->maxTries());
        $job->fire();
        $this->assertTrue($job->hasFailed());
        $this->assertStringNotContainsString($owner->email, $failure->getMessage());
        $this->assertStringContainsString('jangan ulangi', $failure->getMessage());
        $this->assertNull(Queue::connection('database')->pop());
    }

    public function test_restore_reconciliation_discards_only_auth_jobs_and_tokens(): void
    {
        [, $owner] = $this->tenant();
        app(AccountService::class)->requestReset($owner->email);
        $payload = DB::table('jobs')->value('payload');
        DB::table('failed_jobs')->insert(['uuid' => (string) Str::uuid(), 'connection' => 'database', 'queue' => 'default', 'payload' => $payload, 'exception' => 'sanitized', 'failed_at' => now()]);
        $other = DB::table('jobs')->insertGetId(['queue' => 'default', 'payload' => json_encode(['displayName' => 'FutureOperationalJob']), 'attempts' => 0, 'available_at' => time(), 'created_at' => time()]);
        $this->artisan('app:reconcile-auth-restore')->assertFailed();
        $this->assertSame(2, DB::table('jobs')->count());
        config(['outbound.hold' => true]);
        $this->artisan('app:reconcile-auth-restore')->assertSuccessful();
        $this->assertSame([$other], DB::table('jobs')->pluck('id')->all());
        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    public function test_inactive_reset_and_exact_expiry_preserve_account_state(): void
    {
        [, $owner] = $this->tenant();
        $owner->forceFill(['is_active' => false])->save();
        $token = Str::random(64);
        DB::table('password_reset_tokens')->insert(['email' => $owner->email, 'token' => Hash::make($token), 'created_at' => now()->subMinutes(60)]);
        try {
            app(AccountService::class)->resetPassword($owner->email, $token, $this->password());
            $this->fail('Expired token accepted');
        } catch (ValidationException) {
        }
        DB::table('password_reset_tokens')->where('email', $owner->email)->update(['created_at' => now()]);
        app(AccountService::class)->resetPassword($owner->email, $token, $this->password());
        $this->assertFalse($owner->fresh()->is_active);
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }
}

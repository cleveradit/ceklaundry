<?php

namespace Tests\Feature\Notification;

use App\Jobs\SendNotification;
use App\Models\Business;
use App\Models\User;
use App\Services\CustomerMergeService;
use App\Services\CustomerService;
use App\Services\ManualNotificationService;
use App\Services\NotificationDispatcher;
use App\Services\NotificationRecoveryService;
use App\Services\NotificationSettingsService;
use App\Services\NotificationTransport;
use App\Services\ReminderScheduler;
use App\Services\TransactionEmailVerificationService;
use App\Services\TransactionStateMachine;
use App\Services\WaQuotaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\FoundationTestCase;

class NotificationFlowTest extends FoundationTestCase
{
    public function test_ready_reserves_once_and_worker_records_provider_acceptance(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, ['notification_email' => 'pelanggan@example.test']);
        app(TransactionStateMachine::class)->move($owner, $id, 'DIPROSES', 1);
        app(TransactionStateMachine::class)->move($owner, $id, 'SIAP_DIAMBIL', 2);
        app(TransactionStateMachine::class)->move($owner, $id, 'SIAP_DIAMBIL', 3);
        $logs = DB::table('notification_logs')->where('transaction_id', $id)->get();
        $this->assertCount(1, $logs);
        $this->assertSame('tertunda', $logs[0]->status);
        $this->assertSame('tx:'.$id.':ready:email:0', $logs[0]->notification_key);
        $this->assertSame(1, DB::table('jobs')->where('payload->business_id', $business->id)->count());
        $transport = new class extends NotificationTransport
        {
            public int $calls = 0;

            public function send(array $envelope): array
            {
                $this->calls++;

                return ['outcome' => 'accepted', 'code' => null];
            }
        };
        $this->inTenant($business, fn () => (new SendNotification($business->id, $logs[0]->id))->handle($transport));
        $this->assertSame(1, $transport->calls);
        $this->assertSame('berhasil', DB::table('notification_logs')->find($logs[0]->id)->status);
        $this->inTenant($business, fn () => (new SendNotification($business->id, $logs[0]->id))->handle($transport));
        $this->assertSame(1, $transport->calls);
    }

    public function test_public_email_confirmation_changes_only_one_transaction(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, ['kode_resi' => 'ABC234']);
        $tx = DB::table('transactions')->find($id);
        $other = $this->transaction($business, $owner, $branch->id, ['customer_id' => $tx->customer_id, 'kode_resi' => 'ABC235']);
        $request = Request::create('/t/ABC234/email', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);
        app(TransactionEmailVerificationService::class)->request('ABC234', 'baru@example.test', $request);
        $pending = DB::table('transactions')->find($id);
        $this->assertNull($pending->notification_email);
        $this->assertSame('baru@example.test', $pending->pending_notification_email);
        $this->assertSame(1, DB::table('notification_logs')->where('transaction_id', $id)->where('tipe', 'verifikasi_email')->count());
        app(TransactionEmailVerificationService::class)->confirm('ABC234', $pending->email_verification_version);
        $this->assertSame('baru@example.test', DB::table('transactions')->find($id)->notification_email);
        $this->assertNull(DB::table('transactions')->find($other)->notification_email);
        $this->assertNull(DB::table('customers')->find($tx->customer_id)->email);
    }

    public function test_reminder_cursor_advances_once_when_channel_exists(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'notification_email' => 'pelanggan@example.test', 'status' => 'SIAP_DIAMBIL',
            'waktu_siap_diambil' => now('Asia/Jakarta')->subDays(3),
        ]);
        app(ReminderScheduler::class)->run();
        $this->assertSame(1, DB::table('transactions')->find($id)->reminder_count);
        $this->assertSame(1, DB::table('notification_logs')->where('transaction_id', $id)->where('tipe', 'pengingat')->count());
        app(ReminderScheduler::class)->run();
        $this->assertSame(1, DB::table('transactions')->find($id)->reminder_count);
    }

    public function test_owner_cannot_reduce_limit_below_occupied(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id);
        $this->notification($business, $id, ['kanal' => 'whatsapp', 'tujuan' => '6281234567890', 'wa_quota_month' => now('Asia/Jakarta')->startOfMonth()->toDateString()]);
        $this->expectException(ValidationException::class);
        app(NotificationSettingsService::class)->behavior($owner, [
            'reminder_enabled' => true, 'reminder_first_days' => 2, 'reminder_interval_days' => 2,
            'reminder_max_count' => 3, 'wa_on_ready' => true, 'wa_on_reminder' => false, 'wa_monthly_limit' => 0,
        ]);
    }

    public function test_number_change_retargets_unattempted_wa_and_reviews_attempted_wa(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
        ]);
        $business->forceFill(['wa_enabled' => true, 'wa_provider' => 'fonnte', 'wa_token' => 'test-token', 'wa_sender_number' => '6281234567890'])->save();
        $tx = DB::table('transactions')->find($id);
        $old = DB::table('customers')->find($tx->customer_id)->no_hp;
        $log = $this->notification($business, $id, ['kanal' => 'whatsapp', 'tujuan' => $old,
            'wa_quota_month' => now('Asia/Jakarta')->startOfMonth()->toDateString()]);
        app(CustomerService::class)->save($owner, ['nama' => 'Pelanggan Uji', 'no_hp' => '081234567890'], $tx->customer_id);
        $this->assertSame('6281234567890', DB::table('notification_logs')->find($log)->tujuan);
        $this->assertSame('tertunda', DB::table('notification_logs')->find($log)->status);
        DB::table('notification_logs')->where('id', $log)->update(['attempt_count' => 1, 'delivery_started_at' => now()]);
        app(CustomerService::class)->save($owner, ['nama' => 'Pelanggan Uji', 'no_hp' => '081234567891'], $tx->customer_id);
        $changed = DB::table('notification_logs')->find($log);
        $this->assertSame('perlu_pemeriksaan', $changed->status);
        $this->assertSame('recipient_berubah', $changed->reason_code);
        $this->assertSame('6281234567890', $changed->tujuan);
    }

    public function test_merge_retargets_pending_wa_and_revokes_attempted_wa_token(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $source = app(CustomerService::class)->save($owner, ['nama' => 'Sumber WA', 'no_hp' => '081355500011']);
        $target = app(CustomerService::class)->save($owner, ['nama' => 'Tujuan WA', 'no_hp' => '081355500012']);
        $business->forceFill(['wa_enabled' => true, 'wa_provider' => 'fonnte', 'wa_token' => 'test-token',
            'wa_sender_number' => '6281234567890'])->save();
        DB::table('business_settings')->where('business_id', $business->id)->update(['wa_on_ready' => true]);
        $ready = ['customer_id' => $source, 'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta')];
        $pendingTx = $this->transaction($business, $owner, $branch->id, $ready);
        $attemptedTx = $this->transaction($business, $owner, $branch->id, $ready);
        $pending = $this->notification($business, $pendingTx, ['kanal' => 'whatsapp', 'tujuan' => '6281355500011',
            'wa_quota_month' => now('Asia/Jakarta')->startOfMonth()->toDateString()]);
        $token = (string) Str::uuid();
        $attempted = $this->notification($business, $attemptedTx, ['kanal' => 'whatsapp', 'tujuan' => '6281355500011',
            'status' => 'diproses', 'processing_token' => $token, 'processing_started_at' => now(),
            'delivery_started_at' => now(), 'attempt_count' => 1,
            'wa_quota_month' => now('Asia/Jakarta')->startOfMonth()->toDateString()]);

        app(CustomerMergeService::class)->merge($owner, $source, $target);

        $this->assertSame($target, DB::table('transactions')->find($pendingTx)->customer_id);
        $this->assertSame('6281355500012', DB::table('notification_logs')->find($pending)->tujuan);
        $this->assertSame('tertunda', DB::table('notification_logs')->find($pending)->status);
        $review = DB::table('notification_logs')->find($attempted);
        $this->assertSame('perlu_pemeriksaan', $review->status);
        $this->assertSame('6281355500011', $review->tujuan);
        $this->assertNull($review->processing_token);
    }

    public function test_manual_email_replay_keeps_one_log_and_new_key_obeys_cooldown(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
            'notification_email' => 'pelanggan@example.test',
        ]);
        $key = (string) Str::uuid();
        $first = app(ManualNotificationService::class)->email($owner, $id, ['request_key' => $key]);
        $second = app(ManualNotificationService::class)->email($owner, $id, ['request_key' => $key]);
        $this->assertSame($first, $second);
        $this->assertSame(1, DB::table('notification_logs')->where('transaction_id', $id)->count());
        try {
            app(ManualNotificationService::class)->email($owner, $id, ['request_key' => (string) Str::uuid()]);
            $this->fail('Cooldown email manual tidak diterapkan.');
        } catch (HttpException $exception) {
            $this->assertSame(429, $exception->getStatusCode());
        }
    }

    public function test_restore_hold_preserves_laundry_status_without_new_outbound(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, ['notification_email' => 'pelanggan@example.test']);
        config()->set('outbound.hold', true);
        app(TransactionStateMachine::class)->move($owner, $id, 'DIPROSES', 1);
        app(TransactionStateMachine::class)->move($owner, $id, 'SIAP_DIAMBIL', 2);
        $this->assertSame('SIAP_DIAMBIL', DB::table('transactions')->find($id)->status);
        $this->assertSame(0, DB::table('notification_logs')->where('transaction_id', $id)->count());
        config()->set('outbound.hold', false);
    }

    public function test_log_and_database_job_roll_back_together(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
            'notification_email' => 'pelanggan@example.test',
        ]);
        try {
            DB::transaction(function () use ($business, $id) {
                $locked = Business::query()->lockForUpdate()->findOrFail($business->id);
                $tx = DB::table('transactions')->where('id', $id)->first();
                $this->inTenant($business, fn () => app(NotificationDispatcher::class)->reserve($locked, $tx, 'siap_diambil', 'email'));
                throw new \RuntimeException('fault before commit');
            });
            $this->fail('Fault injection did not run.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('fault before commit', $exception->getMessage());
        }
        $this->assertSame(0, DB::table('notification_logs')->where('transaction_id', $id)->count());
        $this->assertSame(0, DB::table('jobs')->where('payload->business_id', $business->id)->count());
    }

    public function test_expired_marker_is_reviewed_without_reenqueuing(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
            'notification_email' => 'pelanggan@example.test',
        ]);
        $log = $this->notification($business, $id, [
            'status' => 'diproses', 'next_attempt_at' => null, 'processing_token' => (string) Str::uuid(),
            'processing_started_at' => now()->subMinutes(6), 'delivery_started_at' => now()->subMinutes(6),
            'attempt_count' => 1,
        ]);
        app(NotificationRecoveryService::class)->recover();
        $this->assertSame('perlu_pemeriksaan', DB::table('notification_logs')->find($log)->status);
        $this->assertSame(0, DB::table('jobs')->where('payload->business_id', $business->id)->count());
    }

    public function test_public_confirmation_get_is_read_only_and_stale_post_is_rejected(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, ['kode_resi' => 'ABC236']);
        $this->post('/t/ABC236/email', ['email' => 'baru@example.test'])->assertRedirect('/t/ABC236');
        $before = DB::table('transactions')->find($id);
        $url = URL::temporarySignedRoute('receipt.email.confirm', now()->addHour(),
            ['kodeResi' => 'ABC236', 'version' => $before->email_verification_version]);
        $this->assertStringNotContainsString('baru@example.test', $url);
        $this->get($url)->assertOk();
        $this->assertNull(DB::table('transactions')->find($id)->notification_email);
        $this->post($url)->assertRedirect('/t/ABC236');
        $this->assertSame('baru@example.test', DB::table('transactions')->find($id)->notification_email);
        $this->post($url)->assertStatus(422);
    }

    public function test_developer_secret_is_masked_and_owner_cannot_open_technical_page(): void
    {
        [$business, $owner] = $this->tenant();
        $developer = User::query()->where('role', 'developer')->firstOrFail();
        app(NotificationSettingsService::class)->technical($developer, $business->id, [
            'wa_enabled' => false, 'wa_provider' => 'fonnte', 'wa_sender_number' => '6281234567890',
            'wa_token' => 'very-secret-wa-token', 'email_sender_name' => 'Laundry QA',
            'email_sender_address' => 'sender@example.test',
            'smtp_config' => ['host' => 'smtp.example.test', 'port' => 587, 'username' => 'user',
                'password' => 'very-secret-smtp-password', 'encryption' => 'tls'],
        ]);
        $response = $this->actingAs($developer)->get('/dev/businesses/'.$business->id.'/notifications')->assertOk();
        $response->assertDontSee('very-secret-wa-token')->assertDontSee('very-secret-smtp-password');
        $this->actingAs($owner)->get('/dev/businesses/'.$business->id.'/notifications')->assertForbidden();
    }

    public function test_definite_rejection_retries_three_calls_but_unknown_never_retries(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
            'notification_email' => 'pelanggan@example.test',
        ]);
        $log = $this->inTenant($business, fn () => DB::transaction(function () use ($business, $id) {
            $locked = Business::query()->lockForUpdate()->findOrFail($business->id);

            return app(NotificationDispatcher::class)->reserve($locked, DB::table('transactions')->find($id), 'siap_diambil', 'email');
        }));
        $rejected = new class extends NotificationTransport
        {
            public int $calls = 0;

            public function send(array $envelope): array
            {
                $this->calls++;

                return ['outcome' => 'definitely_rejected', 'code' => 'provider_menolak'];
            }
        };
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            DB::table('notification_logs')->where('id', $log)->update(['next_attempt_at' => now()->subSecond()]);
            $this->inTenant($business, fn () => (new SendNotification($business->id, $log))->handle($rejected));
            $this->assertSame($attempt, DB::table('notification_logs')->find($log)->attempt_count);
        }
        $this->assertSame(3, $rejected->calls);
        $this->assertSame('gagal', DB::table('notification_logs')->find($log)->status);

        $other = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
            'notification_email' => 'lain@example.test',
        ]);
        $uncertain = $this->inTenant($business, fn () => DB::transaction(function () use ($business, $other) {
            $locked = Business::query()->lockForUpdate()->findOrFail($business->id);

            return app(NotificationDispatcher::class)->reserve($locked, DB::table('transactions')->find($other), 'siap_diambil', 'email');
        }));
        $unknown = new class extends NotificationTransport
        {
            public int $calls = 0;

            public function send(array $envelope): array
            {
                $this->calls++;

                return ['outcome' => 'unknown', 'code' => 'smtp_tidak_pasti'];
            }
        };
        $this->inTenant($business, fn () => (new SendNotification($business->id, $uncertain))->handle($unknown));
        $this->inTenant($business, fn () => (new SendNotification($business->id, $uncertain))->handle($unknown));
        $this->assertSame(1, $unknown->calls);
        $this->assertSame('perlu_pemeriksaan', DB::table('notification_logs')->find($uncertain)->status);
    }

    public function test_reminder_off_then_on_does_not_revive_old_pending_log(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
        ]);
        $log = $this->notification($business, $id, ['tipe' => 'pengingat', 'reminder_number' => 1]);
        $data = ['reminder_first_days' => 2, 'reminder_interval_days' => 2, 'reminder_max_count' => 3,
            'wa_on_ready' => true, 'wa_on_reminder' => false, 'wa_monthly_limit' => null];
        app(NotificationSettingsService::class)->behavior($owner, ['reminder_enabled' => false, ...$data]);
        $this->assertSame('dilewati_kondisi', DB::table('notification_logs')->find($log)->status);
        app(NotificationSettingsService::class)->behavior($owner, ['reminder_enabled' => true, ...$data]);
        $this->assertSame('dilewati_kondisi', DB::table('notification_logs')->find($log)->status);
    }

    public function test_manual_wa_open_logs_only_opened_status_and_no_api_slot(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
        ]);
        $key = (string) Str::uuid();
        $first = app(ManualNotificationService::class)->whatsappLink($owner, $id, 'pengingat', $key);
        $second = app(ManualNotificationService::class)->whatsappLink($owner, $id, 'pengingat', $key);
        $this->assertSame($first, $second);
        $this->assertStringStartsWith('https://wa.me/', $first);
        $this->assertStringContainsString('Pengingat%20pengambilan', $first);
        $log = DB::table('notification_logs')->where('transaction_id', $id)->first();
        $this->assertSame('whatsapp_manual', $log->kanal);
        $this->assertSame('dibuka_manual', $log->status);
        $this->assertNull($log->wa_quota_month);
        $this->assertSame(1, DB::table('notification_logs')->where('transaction_id', $id)->count());
    }

    public function test_read_only_public_page_hides_email_form_and_post_is_locked(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $this->transaction($business, $owner, $branch->id, ['kode_resi' => 'ABC237']);
        $business->forceFill(['active_until' => now('Asia/Jakarta')->subDays(10)->toDateString()])->save();
        $this->get('/t/ABC237')->assertOk()->assertDontSee('Kirim tautan konfirmasi');
        $this->post('/t/ABC237/email', ['email' => 'baru@example.test'])->assertStatus(423);
    }

    public function test_wa_bucket_resets_monthly_and_pending_old_month_obeys_current_limit(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $business->forceFill(['wa_enabled' => true, 'wa_provider' => 'fonnte', 'wa_token' => 'test-token',
            'wa_sender_number' => '6281234567890'])->save();
        DB::table('business_settings')->where('business_id', $business->id)->update(['wa_monthly_limit' => 0]);
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
        ]);
        $phone = DB::table('customers')->where('id', DB::table('transactions')->find($id)->customer_id)->value('no_hp');
        $oldMonth = now('Asia/Jakarta')->subMonth()->startOfMonth()->toDateString();
        $log = $this->notification($business, $id, ['kanal' => 'whatsapp', 'tujuan' => $phone,
            'wa_quota_month' => $oldMonth]);
        $this->assertSame(0, app(WaQuotaService::class)->occupied($business->id));
        $fake = new class extends NotificationTransport
        {
            public int $calls = 0;

            public function send(array $envelope): array
            {
                $this->calls++;

                return ['outcome' => 'accepted', 'code' => null];
            }
        };
        $this->inTenant($business, fn () => (new SendNotification($business->id, $log))->handle($fake));
        $this->assertSame(0, $fake->calls);
        $this->assertSame('dilewati_batas', DB::table('notification_logs')->find($log)->status);
        $this->assertSame(0, app(WaQuotaService::class)->occupied($business->id));
    }

    public function test_reminder_uses_n_m_k_without_burst_and_waits_for_channel(): void
    {
        Carbon::setTestNow('2026-09-01 08:00:00');
        try {
            [$business, $owner] = $this->tenant();
            $branch = $this->branch($owner);
            $readyAt = now('Asia/Jakarta');
            $id = $this->transaction($business, $owner, $branch->id, [
                'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => $readyAt,
            ]);
            Carbon::setTestNow('2026-09-03 08:00:00');
            app(ReminderScheduler::class)->run();
            $this->assertSame(0, DB::table('transactions')->find($id)->reminder_count);
            DB::table('transactions')->where('id', $id)->update(['notification_email' => 'pelanggan@example.test']);
            foreach (['2026-09-03 08:00:00', '2026-09-05 08:00:00', '2026-09-07 08:00:00', '2026-09-09 08:00:00'] as $index => $time) {
                Carbon::setTestNow($time);
                app(ReminderScheduler::class)->run();
                $this->assertSame(min($index + 1, 3), DB::table('transactions')->find($id)->reminder_count);
            }
            $this->assertSame(3, DB::table('notification_logs')->where('transaction_id', $id)->where('tipe', 'pengingat')->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_public_email_fourth_request_per_receipt_is_rate_limited(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $id = $this->transaction($business, $owner, $branch->id, ['kode_resi' => 'ABC238']);
        for ($i = 0; $i < 3; $i++) {
            $this->post('/t/ABC238/email', ['email' => 'baru@example.test'])->assertRedirect('/t/ABC238');
        }
        $this->post('/t/ABC238/email', ['email' => 'baru@example.test'])->assertStatus(429);
        $this->assertNull(DB::table('transactions')->find($id)->notification_email);
        $this->assertSame(3, DB::table('notification_logs')->where('transaction_id', $id)->where('tipe', 'verifikasi_email')->count());
    }

    public function test_late_provider_acceptance_cannot_overwrite_recipient_review(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $business->forceFill(['wa_enabled' => true, 'wa_provider' => 'fonnte', 'wa_token' => 'test-token',
            'wa_sender_number' => '6281234567890'])->save();
        $id = $this->transaction($business, $owner, $branch->id, [
            'status' => 'SIAP_DIAMBIL', 'waktu_siap_diambil' => now('Asia/Jakarta'),
        ]);
        $tx = DB::table('transactions')->find($id);
        $phone = DB::table('customers')->find($tx->customer_id)->no_hp;
        $log = $this->notification($business, $id, ['kanal' => 'whatsapp', 'tujuan' => $phone,
            'wa_quota_month' => now('Asia/Jakarta')->startOfMonth()->toDateString()]);
        $transport = new class($owner, $tx->customer_id) extends NotificationTransport
        {
            public function __construct(private $actor, private int $customerId) {}

            public function send(array $envelope): array
            {
                app(CustomerService::class)->save($this->actor, ['nama' => 'Pelanggan Uji', 'no_hp' => '081234567890'], $this->customerId);

                return ['outcome' => 'accepted', 'code' => null];
            }
        };
        $this->inTenant($business, fn () => (new SendNotification($business->id, $log))->handle($transport));
        $result = DB::table('notification_logs')->find($log);
        $this->assertSame('perlu_pemeriksaan', $result->status);
        $this->assertSame('recipient_berubah', $result->reason_code);
        $this->assertNull($result->processing_token);
    }
}

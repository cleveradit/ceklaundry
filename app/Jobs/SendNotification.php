<?php

namespace App\Jobs;

use App\Jobs\Middleware\UseTenantContext;
use App\Models\Business;
use App\Services\LifecycleService;
use App\Services\NotificationTransport;
use App\Services\OutboundGuard;
use App\Services\WaQuotaService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class SendNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 0;

    public int $timeout = 120;

    public function __construct(public int $businessId, public int $logId)
    {
        $this->onConnection('database');
    }

    public function middleware(): array
    {
        return [new UseTenantContext];
    }

    public function handle(NotificationTransport $transport): void
    {
        try {
            $token = $this->claim();
            if (! $token) {
                return;
            }
            $envelope = $this->mark($token);
            if (! $envelope) {
                return;
            }
            try {
                $result = $transport->send($envelope);
            } catch (Throwable) {
                $result = ['outcome' => 'unknown', 'code' => 'transport_unknown'];
            }
            $this->finish($token, $result);
        } catch (Throwable) {
            // Recovery classifies a missing job or an expired delivery marker. Never log recipient or provider details.
            Log::warning('notification_job_incomplete', ['business_id' => $this->businessId, 'log_id' => $this->logId]);
        }
    }

    private function locked(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            $business = Business::query()->lockForUpdate()->find($this->businessId);
            if (! $business) {
                return null;
            }
            $parent = DB::table('notification_logs')->where('business_id', $business->id)->where('id', $this->logId)->first();
            if (! $parent) {
                return null;
            }
            $txParent = DB::table('transactions')->where('business_id', $business->id)->where('id', $parent->transaction_id)->first();
            if (! $txParent) {
                return null;
            }
            $customer = DB::table('customers')->where('business_id', $business->id)->where('id', $txParent->customer_id)->lockForUpdate()->first();
            $tx = DB::table('transactions')->where('business_id', $business->id)->where('id', $txParent->id)->lockForUpdate()->first();
            $log = DB::table('notification_logs')->where('business_id', $business->id)->where('id', $this->logId)->lockForUpdate()->first();

            return $callback($business, $customer, $tx, $log);
        }, 3);
    }

    private function claim(): ?string
    {
        return $this->locked(function ($business, $customer, $tx, $log) {
            if ($log->status !== 'tertunda' || ! $log->next_attempt_at || now()->lessThan($log->next_attempt_at)) {
                return null;
            }
            $token = (string) Str::uuid();
            DB::table('notification_logs')->where('id', $log->id)->update([
                'status' => 'diproses', 'processing_token' => $token, 'processing_started_at' => now(), 'next_attempt_at' => null, 'updated_at' => now(),
            ]);

            return $token;
        });
    }

    private function mark(string $token): ?array
    {
        return $this->locked(function ($business, $customer, $tx, $log) use ($token) {
            if ($log->status !== 'diproses' || $log->processing_token !== $token || $log->delivery_started_at ||
                CarbonImmutable::parse($log->processing_started_at)->addSeconds(300)->lessThan(now())) {
                return null;
            }
            $reason = $this->ineligible($business, $customer, $tx, $log);
            if ($reason) {
                if ($reason === 'recipient_berubah' && ! $log->delivery_started_at && $log->attempt_count === 0 && $customer) {
                    DB::table('notification_logs')->where('id', $log->id)->update([
                        'tujuan' => $customer->no_hp, 'status' => 'tertunda', 'processing_token' => null,
                        'processing_started_at' => null, 'next_attempt_at' => now(), 'last_enqueued_at' => now(), 'updated_at' => now(),
                    ]);
                    Bus::dispatch(new self($business->id, $log->id));
                } else {
                    $this->terminal($log->id, $reason === 'recipient_berubah' ? 'perlu_pemeriksaan' : 'dilewati_kondisi', $reason);
                }

                return null;
            }
            if ($log->kanal === 'whatsapp') {
                $month = app(WaQuotaService::class)->month();
                if ((int) $log->attempt_count > 0 && $log->wa_quota_month !== $month) {
                    $this->terminal($log->id, 'dilewati_kondisi', 'bulan_retry_berubah');

                    return null;
                }
                if ((int) $log->attempt_count === 0 && $log->wa_quota_month !== $month) {
                    if (! app(WaQuotaService::class)->available($business->id)) {
                        $this->terminal($log->id, 'dilewati_batas', 'kuota_bulan_ini');

                        return null;
                    }
                    DB::table('notification_logs')->where('id', $log->id)->update(['wa_quota_month' => $month]);
                }
            }
            $snapshot = $log->payload_snapshot ? json_decode($log->payload_snapshot, true, 512, JSON_THROW_ON_ERROR) : $this->payload($business, $tx);
            $provider = $log->provider_name_snapshot ?: ($log->kanal === 'email' ? 'smtp' : $business->wa_provider);
            if ($log->provider_name_snapshot && $log->kanal === 'whatsapp' && $log->provider_name_snapshot !== $business->wa_provider) {
                $this->terminal($log->id, 'dilewati_kondisi', 'provider_berubah');

                return null;
            }
            $at = now('Asia/Jakarta');
            DB::table('notification_logs')->where('id', $log->id)->update([
                'delivery_started_at' => $at, 'attempt_count' => $log->attempt_count + 1,
                'payload_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR), 'provider_name_snapshot' => $provider,
                'updated_at' => $at,
            ]);

            return ['business' => $business, 'recipient' => $log->tujuan, 'channel' => $log->kanal,
                'provider' => $provider, 'payload' => $snapshot, 'type' => $log->tipe, 'created_at' => $log->created_at,
                'ready_at' => $tx->waktu_siap_diambil, 'verification_version' => $log->verification_version,
                'is_manual' => (bool) $log->is_manual];
        });
    }

    private function finish(string $token, array $result): void
    {
        $this->locked(function ($business, $customer, $tx, $log) use ($token, $result) {
            if (! in_array($log->status, ['diproses', 'perlu_pemeriksaan'], true) || $log->processing_token !== $token) {
                return;
            }
            $reason = $this->ineligible($business, $customer, $tx, $log);
            if ($reason === 'recipient_berubah' || $reason === 'restore_hold' || $reason === 'restore_cutoff') {
                $this->terminal($log->id, 'perlu_pemeriksaan', $reason);

                return;
            }
            $outcome = $result['outcome'] ?? 'unknown';
            $code = $result['code'] ?? null;
            if ($outcome === 'accepted') {
                DB::table('notification_logs')->where('id', $log->id)->update([
                    'status' => 'berhasil', 'sent_at' => now(), 'reason_code' => null, 'processing_token' => null,
                    'processing_started_at' => null, 'updated_at' => now(),
                ]);
            } elseif ($outcome === 'definitely_rejected' && $log->attempt_count < 3 &&
                ($log->kanal !== 'whatsapp' || $log->wa_quota_month === app(WaQuotaService::class)->month())) {
                $at = now()->addSeconds($log->attempt_count === 1 ? 60 : 300);
                DB::table('notification_logs')->where('id', $log->id)->update([
                    'status' => 'tertunda', 'reason_code' => $code, 'processing_token' => null,
                    'processing_started_at' => null, 'delivery_started_at' => null, 'next_attempt_at' => $at,
                    'last_enqueued_at' => now(), 'updated_at' => now(),
                ]);
                // The database queue row is committed with the retry state; its delay is the next due time.
                Bus::dispatch((new self($business->id, $log->id))->delay($at));
            } else {
                $this->terminal($log->id, $outcome === 'definitely_rejected' ? 'gagal' : 'perlu_pemeriksaan',
                    $outcome === 'definitely_rejected' ? ($code ?: 'ditolak_provider') : 'hasil_tidak_pasti');
            }
        });
    }

    private function ineligible(Business $business, ?object $customer, object $tx, object $log): ?string
    {
        if (! app(OutboundGuard::class)->allows($business, $log->created_at)) {
            return config('outbound.hold') ? 'restore_hold' : 'restore_cutoff';
        }
        if (! app(LifecycleService::class)->writable($business)) {
            return 'tenant_readonly';
        }
        if (! $customer) {
            return 'pelanggan_hilang';
        }
        if ($log->tipe === 'verifikasi_email') {
            if (! in_array($tx->status, ['DITERIMA', 'DIPROSES'], true) ||
                (int) $tx->email_verification_version !== (int) $log->verification_version ||
                $tx->pending_notification_email !== $log->tujuan || now()->greaterThanOrEqualTo($tx->email_verification_expires_at)) {
                return 'verifikasi_kedaluwarsa';
            }
        } else {
            if ($tx->status !== 'SIAP_DIAMBIL' || (! $log->is_manual && ! app(OutboundGuard::class)->allows($business, $tx->waktu_siap_diambil))) {
                return 'transaksi_terminal';
            }
            $settings = DB::table('business_settings')->where('business_id', $business->id)->first();
            if ($log->tipe === 'pengingat' && ! $log->is_manual && (! $settings->reminder_enabled || $log->reminder_number > $settings->reminder_max_count)) {
                return 'pengingat_nonaktif';
            }
            if ($log->kanal === 'whatsapp' && (! $business->wa_enabled ||
                ($log->tipe === 'siap_diambil' && ! $settings->wa_on_ready) || ($log->tipe === 'pengingat' && ! $settings->wa_on_reminder))) {
                return 'wa_nonaktif';
            }
            if ($log->kanal === 'email' && $log->tujuan !== $tx->notification_email) {
                return 'email_berubah';
            }
        }
        if ($log->kanal === 'whatsapp' && $customer->no_hp !== $log->tujuan) {
            return 'recipient_berubah';
        }

        return null;
    }

    private function payload(Business $business, object $tx): array
    {
        $branch = DB::table('branches')->where('business_id', $business->id)->where('id', $tx->branch_id)->first();
        $paid = (int) DB::table('payments')->where('business_id', $business->id)->where('transaction_id', $tx->id)->sum('jumlah');

        return ['business' => $business->nama, 'branch' => $branch?->nama ?? $business->nama,
            'code' => $tx->kode_resi, 'total' => (int) $tx->total_akhir,
            'stamp_discount' => (int) $tx->potongan_stempel, 'promo_discount' => (int) $tx->potongan_promo,
            'promo_name' => $tx->promo_nama_snapshot,
            'remaining' => max(0, (int) $tx->total_akhir - $paid), 'url' => url('/t/'.$tx->kode_resi),
            'provider_options' => $business->wa_config ? array_diff_key($business->wa_config, array_flip(['secret_key'])) : []];
    }

    private function terminal(int $id, string $status, string $reason): void
    {
        DB::table('notification_logs')->where('id', $id)->update([
            'status' => $status, 'reason_code' => $reason, 'processing_token' => null,
            'processing_started_at' => null, 'next_attempt_at' => null, 'updated_at' => now(),
        ]);
    }
}

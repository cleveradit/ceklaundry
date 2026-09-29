<?php

namespace App\Console\Commands;

use App\Jobs\SendNotification;
use App\Models\Business;
use App\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileNotificationRestore extends Command
{
    protected $signature = 'app:reconcile-notification-restore';

    protected $description = 'Cabut pekerjaan outbound transaksi sebelum membuka hold restore';

    public function handle(): int
    {
        if (filter_var(config('outbound.hold'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== true) {
            $this->error('Aktifkan OUTBOUND_RESTORE_HOLD=true di semua proses dahulu.');

            return self::FAILURE;
        }
        foreach (Business::query()->orderBy('id')->pluck('id') as $id) {
            DB::transaction(function () use ($id) {
                $business = Business::query()->lockForUpdate()->find($id);
                if (! $business) {
                    return;
                }
                app(TenantContext::class)->run($id, function () use ($id) {
                    DB::table('notification_logs')->where('business_id', $id)
                        ->whereIn('status', ['tertunda', 'diproses'])->update([
                            'status' => 'perlu_pemeriksaan', 'reason_code' => 'restore_hold',
                            'processing_token' => null, 'processing_started_at' => null,
                            'next_attempt_at' => null, 'updated_at' => now(),
                        ]);
                    DB::table('transactions')->where('business_id', $id)->whereNotNull('pending_notification_email')
                        ->update(['pending_notification_email' => null, 'email_verification_expires_at' => null,
                            'email_verification_version' => DB::raw('email_verification_version + 1'), 'updated_at' => now()]);
                    foreach (['jobs', 'failed_jobs'] as $table) {
                        DB::table($table)->where('payload->displayName', SendNotification::class)
                            ->where('payload->business_id', $id)->delete();
                    }
                });
            }, 3);
        }
        $this->info('Log nonterminal dan job notifikasi lama telah dicabut. Rekonsiliasi efek eksternal tetap diperlukan.');

        return self::SUCCESS;
    }
}

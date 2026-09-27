<?php

namespace App\Console\Commands;

use App\Jobs\SendPasswordReset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileAuthRestore extends Command
{
    protected $signature = 'app:reconcile-auth-restore';

    protected $description = 'Membuang token/job email keamanan lama saat outbound restore hold aktif.';

    public function handle(): int
    {
        if (filter_var(config('outbound.hold'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== true) {
            $this->error('Aktifkan OUTBOUND_RESTORE_HOLD dan hentikan producer/worker sebelum rekonsiliasi.');

            return self::FAILURE;
        }
        DB::transaction(function () {
            DB::table('password_reset_tokens')->delete();
            foreach (['jobs', 'failed_jobs'] as $table) {
                DB::table($table)->where('payload->displayName', SendPasswordReset::class)->delete();
            }
        });
        $this->info('Token dan job auth lama dibuang. Tetapkan cutoff seragam sebelum membuka outbound kembali.');

        return self::SUCCESS;
    }
}

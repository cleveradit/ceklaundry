<?php

namespace App\Console\Commands;

use App\Services\DemoPurgeService;
use Illuminate\Console\Command;

class PurgeExpiredDemos extends Command
{
    protected $signature = 'app:purge-expired-demos';

    protected $description = 'Hapus tenant demo yang telah kedaluwarsa beserta data terkait.';

    public function handle(DemoPurgeService $purge): int
    {
        $this->info('Demo yang dibersihkan: '.$purge->run());

        return self::SUCCESS;
    }
}

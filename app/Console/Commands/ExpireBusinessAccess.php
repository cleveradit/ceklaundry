<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\LifecycleService;
use App\Services\PendingNotificationInvalidator;
use App\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExpireBusinessAccess extends Command
{
    protected $signature = 'app:expire-business-access';

    protected $description = 'Menutup pekerjaan tertunda untuk akses bisnis yang telah berakhir.';

    public function handle(): int
    {
        Business::query()->select('id')->chunkById(100, function ($businesses) {
            foreach ($businesses as $row) {
                DB::transaction(function () use ($row) {
                    $business = Business::query()->lockForUpdate()->find($row->id);
                    if ($business && ! app(LifecycleService::class)->writable($business)) {
                        app(TenantContext::class)->run($business->id, fn () => app(PendingNotificationInvalidator::class)->invalidate('tenant_readonly'));
                    }
                }, 3);
            }
        });
        Cache::put('scheduler:heartbeat', now()->toIso8601String(), now()->addDay());

        return self::SUCCESS;
    }
}

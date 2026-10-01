<?php

namespace Tests;

use App\Models\Business;
use App\Models\User;
use App\Services\OwnerReportService;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class OwnerReportTestCase extends ConcurrentTestCase
{
    public function refreshDatabase(): void
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
            RefreshDatabaseState::$migrated = true;
        }
    }

    protected function payment(Business $business, User $owner, int $transaction, int $amount, string $time): void
    {
        DB::table('payments')->insert(['business_id' => $business->id, 'transaction_id' => $transaction,
            'request_key' => (string) Str::uuid(), 'request_hash' => hash('sha256', Str::random()),
            'jumlah' => $amount, 'metode' => 'tunai', 'waktu' => $time, 'recorded_by' => $owner->id, 'created_at' => $time]);
    }

    protected function filters(User $owner, array $input = [], bool $revenue = false): array
    {
        return app(OwnerReportService::class)->filters($owner, $input, $revenue);
    }
}

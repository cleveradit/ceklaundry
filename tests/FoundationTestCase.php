<?php

namespace Tests;

use App\Models\Business;
use App\Models\User;
use App\Services\BranchService;
use App\Services\TenantProvisioner;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class FoundationTestCase extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        if (! $app->environment('testing') || config('database.connections.mysql.database') !== 'ceklaundry_test') {
            throw new \LogicException('Tes destruktif hanya boleh memakai ceklaundry_test.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function developer(): User
    {
        return User::factory()->create();
    }

    protected function password(): string
    {
        return 'Aa1!'.Str::random(20);
    }

    protected function tenant(?User $developer = null): array
    {
        $developer ??= $this->developer();
        $password = $this->password();
        $business = app(TenantProvisioner::class)->provision($developer, ['nama' => 'Laundry '.Str::random(5), 'active_until' => now()->addMonth()->toDateString(), 'owner' => ['nama' => 'Owner Uji', 'email' => Str::lower(Str::random(12)).'@example.test', 'password' => $password]]);
        $owner = User::query()->where('business_id', $business->id)->firstOrFail();
        $owner->forceFill(['must_change_password' => false])->save();

        return [$business, $owner, $password];
    }

    protected function branch(User $owner, string $name = 'Cabang Utama')
    {
        return app(BranchService::class)->save($owner, ['nama' => $name, 'alamat' => 'Jalan Uji 1', 'telepon' => '081234567890', 'is_active' => true]);
    }

    protected function inTenant(Business $business, callable $callback): mixed
    {
        return app(TenantContext::class)->run($business->id, $callback);
    }

    protected function transaction(Business $business, User $owner, int $branchId, array $override = []): int
    {
        $customerId = DB::table('customers')->insertGetId(['business_id' => $business->id, 'nama' => 'Pelanggan Uji', 'no_hp' => '628'.random_int(100000000, 999999999), 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('transactions')->insertGetId([...[
            'business_id' => $business->id, 'branch_id' => $branchId, 'customer_id' => $customerId, 'created_by' => $owner->id,
            'kode_resi' => Str::upper(Str::random(6)), 'create_request_key' => (string) Str::uuid(), 'create_request_hash' => hash('sha256', Str::random()),
            'status' => 'DITERIMA', 'waktu_masuk' => now(), 'estimasi_selesai' => now()->addDay(), 'subtotal' => 7000, 'total_akhir' => 7000, 'status_bayar' => 'BELUM_BAYAR', 'created_at' => now(), 'updated_at' => now(),
        ], ...$override]);
    }

    protected function notification(Business $business, int $transactionId, array $override = []): int
    {
        return DB::table('notification_logs')->insertGetId([...[
            'business_id' => $business->id, 'transaction_id' => $transactionId, 'kanal' => 'email', 'tipe' => 'siap_diambil', 'reminder_number' => 0,
            'notification_key' => 'tx:'.$transactionId.':'.Str::random(), 'tujuan' => 'customer@example.test', 'status' => 'tertunda', 'next_attempt_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ], ...$override]);
    }
}

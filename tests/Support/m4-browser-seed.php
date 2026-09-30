<?php

use App\Models\User;
use App\Services\LoyaltySettingsService;
use App\Services\PaymentService;
use App\Services\PricingService;
use App\Services\PromoService;
use App\Services\ServiceCatalogService;
use App\Services\TransactionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.connections.mysql.database') !== 'ceklaundry_test') {
    throw new LogicException('Browser fixtures require isolated test database.');
}
$m2 = json_decode(file_get_contents(storage_path('app/private/m2-browser-fixture.json')), true, 512, JSON_THROW_ON_ERROR);
$owner = User::query()->where('email', $m2['ownerEmail'])->firstOrFail();
$branchId = (int) $m2['branchId'];
$master = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci Lipat', 'satuan' => 'kg', 'harga' => 7000,
    'durasi_jam' => 24, 'berat_minimum' => null, 'is_active' => true]);
app(LoyaltySettingsService::class)->save($owner, ['is_active' => true, 'stempel_dibutuhkan' => 1,
    'master_service_id' => $master->id, 'berat_maks_gratis' => '1.0']);
app(PromoService::class)->save($owner, ['nama' => 'Promo M4', 'tipe' => 'persen', 'nilai' => 10, 'minimal_total' => 7000,
    'mulai' => now('Asia/Jakarta')->subDay()->toDateString(), 'selesai' => now('Asia/Jakarta')->addDay()->toDateString(),
    'semua_cabang' => false, 'branch_ids' => [$branchId], 'is_active' => true]);
$promoId = (int) DB::table('promos')->where('business_id', $owner->business_id)->where('nama', 'Promo M4')->value('id');
$customerId = DB::table('customers')->insertGetId(['business_id' => $owner->business_id, 'nama' => 'Pelanggan M4',
    'no_hp' => '628'.random_int(100000000, 999999999), 'created_at' => now(), 'updated_at' => now()]);
$serviceId = (int) DB::table('services')->where('business_id', $owner->business_id)->where('branch_id', $branchId)->where('nama', 'Cuci Lipat')->value('id');
$items = [['service_id' => $serviceId, 'berat_kg' => '2.0']];
$quote = app(PricingService::class)->quote($owner->business_id, $branchId, $items);
$id = app(TransactionService::class)->create($owner, ['branch_id' => $branchId, 'customer_id' => $customerId,
    'items' => $items, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]);
app(PaymentService::class)->store($owner, $id, ['request_key' => (string) Str::uuid(), 'jumlah' => $quote['total_akhir'], 'metode' => 'tunai']);
$path = storage_path('app/private/m4-browser-fixture.json');
file_put_contents($path, json_encode(['customerId' => $customerId, 'promoId' => $promoId], JSON_THROW_ON_ERROR));
chmod($path, 0600);
echo "Isolated M4 browser fixture ready.\n";

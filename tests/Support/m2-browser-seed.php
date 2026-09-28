<?php

use App\Models\User;
use App\Services\BranchService;
use App\Services\ServiceCatalogService;
use App\Services\TenantProvisioner;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.connections.mysql.database') !== 'ceklaundry_test') {
    throw new LogicException('Browser fixtures require isolated test database.');
}
Artisan::call('migrate:fresh', ['--force' => true]);
$password = 'Aa1!'.Str::random(24);
$developer = (new User)->forceFill(['nama' => 'Developer QA', 'email' => 'developer-'.Str::lower(Str::random(8)).'@example.test', 'password' => $password, 'role' => 'developer', 'is_active' => true, 'must_change_password' => false]);
$developer->save();
$ownerEmail = 'owner-'.Str::lower(Str::random(8)).'@example.test';
$business = app(TenantProvisioner::class)->provision($developer, ['nama' => 'Laundry M2 QA', 'active_until' => now()->addMonth()->toDateString(), 'owner' => ['nama' => 'Pemilik QA', 'email' => $ownerEmail, 'password' => $password]]);
$owner = User::query()->where('business_id', $business->id)->where('role', 'owner')->firstOrFail();
$owner->forceFill(['must_change_password' => false])->save();
$branch = app(BranchService::class)->save($owner, ['nama' => 'Cabang QA', 'alamat' => 'Jalan Melati 12', 'telepon' => '081234567890', 'is_active' => true]);
app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci Lipat', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 24, 'berat_minimum' => '2.0', 'is_active' => true], null, $branch->id);
app(ServiceCatalogService::class)->save($owner, ['nama' => 'Setrika', 'satuan' => 'item', 'harga' => 2500, 'durasi_jam' => 12, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
$adminEmail = 'admin-'.Str::lower(Str::random(8)).'@example.test';
$admin = (new User)->forceFill(['nama' => 'Admin QA', 'email' => $adminEmail, 'password' => $password, 'role' => 'admin', 'is_active' => true, 'must_change_password' => false, 'business_id' => $business->id, 'branch_id' => $branch->id]);
$admin->save();
$path = storage_path('app/private/m2-browser-fixture.json');
file_put_contents($path, json_encode(['ownerEmail' => $ownerEmail, 'adminEmail' => $adminEmail, 'password' => $password, 'branchId' => $branch->id]));
chmod($path, 0600);
echo "Isolated M2 browser fixture ready.\n";

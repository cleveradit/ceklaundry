<?php

use App\Models\User;
use App\Services\PaymentService;
use App\Services\PricingService;
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
$account = json_decode(file_get_contents(storage_path('app/private/m2-browser-fixture.json')), true, 512, JSON_THROW_ON_ERROR);
$owner = User::query()->where('email', $account['ownerEmail'])->firstOrFail();
$branch = (int) $account['branchId'];
DB::table('business_settings')->where('business_id', $owner->business_id)->update(['dp_enabled' => true]);
$service = DB::table('services')->where('business_id', $owner->business_id)->where('branch_id', $branch)->where('satuan', 'kg')->first();
$items = [['service_id' => $service->id, 'berat_kg' => '2.0']];
$quote = app(PricingService::class)->quote($owner->business_id, $branch, $items);
$id = app(TransactionService::class)->create($owner, ['branch_id' => $branch, 'customer' => ['nama' => '=Pelanggan M5', 'no_hp' => '081399988877'],
    'items' => $items, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]);
app(PaymentService::class)->store($owner, $id, ['request_key' => (string) Str::uuid(), 'jumlah' => 3000, 'metode' => 'tunai']);
DB::table('transactions')->where('id', $id)->update(['waktu_masuk' => '2026-07-28 12:00:00']);
DB::table('payments')->where('transaction_id', $id)->update(['waktu' => '2026-07-28 12:00:00']);
$code = DB::table('transactions')->where('id', $id)->value('kode_resi');
$path = storage_path('app/private/m5-browser-fixture.json');
file_put_contents($path, json_encode(['code' => $code, 'transactionId' => $id, 'total' => $quote['total_akhir'], 'remaining' => $quote['total_akhir'] - 3000], JSON_THROW_ON_ERROR));
chmod($path, 0600);
echo "Isolated M5 browser fixture ready.\n";

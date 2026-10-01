<?php

use App\Models\User;
use App\Services\BranchService;
use App\Services\TenantProvisioner;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.connections.mysql.database') !== 'ceklaundry_test') {
    throw new LogicException('Capacity fixtures require isolated test database.');
}
$account = json_decode(file_get_contents(storage_path('app/private/m2-browser-fixture.json')), true, 512, JSON_THROW_ON_ERROR);
$developer = User::query()->where('role', 'developer')->firstOrFail();
config(['hashing.bcrypt.rounds' => 4]);
for ($i = DB::table('businesses')->count(); $i < 200; $i++) {
    app(TenantProvisioner::class)->provision($developer, ['nama' => 'Kapasitas '.Str::random(8), 'active_until' => now()->addMonth()->toDateString(),
        'owner' => ['nama' => 'Owner Kapasitas', 'email' => Str::lower(Str::random(16)).'@example.test', 'password' => $account['password']]]);
}
$businesses = DB::table('businesses')->orderBy('id')->get();
$remaining = max(0, 50000 - DB::table('transactions')->count());
$codes = array_fill_keys(DB::table('transactions')->pluck('kode_resi')->all(), true);
$serial = 0;
$marker = hash('sha256', 'M5-capacity');
$now = now('Asia/Jakarta');
foreach ($businesses as $index => $business) {
    $owner = User::query()->where('business_id', $business->id)->where('role', 'owner')->firstOrFail();
    $branchId = DB::table('branches')->where('business_id', $business->id)->where('is_active', true)->value('id');
    $branchId ??= app(BranchService::class)->save($owner, ['nama' => 'Cabang Kapasitas', 'alamat' => 'Jalan Uji', 'telepon' => '081234567890', 'is_active' => true])->id;
    $customer = DB::table('customers')->insertGetId(['business_id' => $business->id, 'nama' => 'Pelanggan Kapasitas', 'no_hp' => '628555'.str_pad((string) $business->id, 7, '0', STR_PAD_LEFT), 'created_at' => $now, 'updated_at' => $now]);
    $count = (int) ceil($remaining / (count($businesses) - $index));
    $transactions = [];
    for ($i = 0; $i < $count; $i++) {
        do {
            $code = 'Z'.str_pad(strtoupper(base_convert((string) ++$serial, 10, 36)), 5, '0', STR_PAD_LEFT);
        } while (isset($codes[$code]));
        $codes[$code] = true;
        $transactions[] = ['business_id' => $business->id, 'branch_id' => $branchId, 'customer_id' => $customer, 'created_by' => $owner->id,
            'kode_resi' => $code, 'create_request_key' => (string) Str::uuid(), 'create_request_hash' => $marker, 'status' => 'DIPROSES',
            'waktu_masuk' => $now->copy()->subDays($i % 30), 'estimasi_selesai' => $now->copy()->addDay(), 'subtotal' => 21000, 'total_akhir' => 21000,
            'status_bayar' => 'DP', 'created_at' => $now, 'updated_at' => $now];
    }
    if ($transactions !== []) {
        DB::table('transactions')->insert($transactions);
    }
    $items = [];
    $payments = [];
    foreach (DB::table('transactions')->where('business_id', $business->id)->where('create_request_hash', $marker)->get(['id', 'waktu_masuk']) as $transaction) {
        $items[] = ['transaction_id' => $transaction->id, 'nama_layanan_snapshot' => 'Cuci Kapasitas', 'satuan_snapshot' => 'kg', 'harga_snapshot' => 7000,
            'berat_minimum_snapshot' => '3.0', 'durasi_jam_snapshot' => 24, 'berat_kg' => '2.0', 'subtotal' => 21000, 'created_at' => $now, 'updated_at' => $now];
        foreach ([3000, 6000] as $amount) {
            $payments[] = ['business_id' => $business->id, 'transaction_id' => $transaction->id, 'request_key' => (string) Str::uuid(),
                'request_hash' => $marker, 'jumlah' => $amount, 'metode' => 'tunai', 'waktu' => $transaction->waktu_masuk, 'recorded_by' => $owner->id, 'created_at' => $now];
        }
    }
    if ($items !== []) {
        DB::table('transaction_items')->insert($items);
        DB::table('payments')->insert($payments);
    }
    $remaining -= $count;
}
echo 'Capacity fixture: '.DB::table('businesses')->count().' businesses, '.DB::table('transactions')->count().' transactions, '.DB::table('payments')->count()." payments.\n";

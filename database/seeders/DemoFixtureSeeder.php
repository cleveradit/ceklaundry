<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\User;
use App\Services\CancellationService;
use App\Services\DemoIdentity;
use App\Services\PricingService;
use App\Services\TransactionService;
use App\Services\TransactionStateMachine;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoFixtureSeeder extends Seeder
{
    public function seed(Business $business, User $owner): User
    {
        $at = CarbonImmutable::parse($business->created_at, 'Asia/Jakarta');
        DB::table('business_settings')->insert([
            'business_id' => $business->id, 'dp_enabled' => true, 'reminder_enabled' => true,
            'wa_on_ready' => true, 'wa_on_reminder' => true, 'wa_monthly_limit' => 100,
            'created_at' => $at, 'updated_at' => $at,
        ]);
        $branches = [];
        foreach (['Cabang Utama', 'Cabang Kedua'] as $index => $name) {
            $branches[] = DB::table('branches')->insertGetId([
                'business_id' => $business->id, 'nama' => $name,
                'alamat' => 'Jl. Contoh No. '.($index + 1).', Jakarta',
                'telepon' => '628120000000'.($index + 1), 'is_active' => true,
                'created_at' => $at, 'updated_at' => $at,
            ]);
        }
        $admin = (new User)->forceFill([
            'nama' => 'Admin Demo', 'email' => app(DemoIdentity::class)->email($business->id, 'admin'),
            'password' => Str::random(64), 'role' => 'admin', 'business_id' => $business->id,
            'branch_id' => $branches[0], 'is_active' => true, 'must_change_password' => false,
        ]);
        $admin->save();

        $catalog = [
            ['Cuci+Setrika', 'kg', 7000, 48, '3.0'],
            ['Express', 'kg', 12000, 6, '3.0'],
            ['Bed Cover', 'item', 25000, 48, null],
        ];
        $services = [];
        $rewardMaster = null;
        foreach ($catalog as [$name, $unit, $price, $hours, $minimum]) {
            $fields = [
                'business_id' => $business->id, 'nama' => $name, 'satuan' => $unit,
                'harga' => $price, 'durasi_jam' => $hours, 'berat_minimum' => $minimum,
                'is_active' => true, 'created_at' => $at, 'updated_at' => $at,
            ];
            $masterId = DB::table('master_services')->insertGetId($fields);
            if ($name === 'Cuci+Setrika') {
                $rewardMaster = $masterId;
            }
            foreach ($branches as $branchId) {
                $services[$branchId][$name] = DB::table('services')->insertGetId([...$fields, 'branch_id' => $branchId]);
            }
        }
        DB::table('loyalty_settings')->insert([
            'business_id' => $business->id, 'is_active' => true, 'stempel_dibutuhkan' => 10,
            'master_service_id' => $rewardMaster, 'berat_maks_gratis' => '3.0',
            'created_at' => $at, 'updated_at' => $at,
        ]);
        $promoId = DB::table('promos')->insertGetId([
            'business_id' => $business->id, 'nama' => 'Demo Hemat', 'tipe' => 'persen',
            'nilai' => 10, 'minimal_total' => null, 'mulai' => $at->toDateString(),
            'selesai' => $at->addDays(7)->toDateString(), 'semua_cabang' => true,
            'is_active' => true, 'created_at' => $at, 'updated_at' => $at,
        ]);

        $customers = [];
        foreach (range(1, 6) as $number) {
            $customers[] = DB::table('customers')->insertGetId([
                'business_id' => $business->id, 'nama' => 'Pelanggan Demo '.$number,
                'no_hp' => '628'.str_pad((string) ($business->id * 10 + $number), 10, '0', STR_PAD_LEFT),
                'email' => "demo-customer-{$business->id}-{$number}@example.invalid",
                'stamp_count' => 0, 'created_at' => $at, 'updated_at' => $at,
            ]);
        }

        for ($index = 0; $index < 15; $index++) {
            $firstTen = $index < 10;
            $branchId = $branches[$firstTen ? ($index % 2) : (($index - 10) % 2)];
            $customerId = $customers[$firstTen ? 0 : $index - 9];
            $serviceName = $index === 11 ? 'Express' : ($index === 14 ? 'Bed Cover' : 'Cuci+Setrika');
            $item = $serviceName === 'Bed Cover'
                ? ['service_id' => $services[$branchId][$serviceName], 'jumlah_unit' => 1]
                : ['service_id' => $services[$branchId][$serviceName], 'berat_kg' => '3.0'];
            $usePromo = $index === 0;
            $quote = app(PricingService::class)->quote($business->id, $branchId, [$item], $customerId, null, $usePromo ? $promoId : null);
            $full = $firstTen || in_array($index, [13, 14], true);
            $partial = in_array($index, [10, 12], true);
            $payment = $full ? (int) $quote['total_akhir'] : ($partial ? intdiv((int) $quote['total_akhir'], 2) : null);
            $data = [
                'branch_id' => $branchId, 'customer_id' => $customerId, 'items' => [$item],
                'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint'],
                'promo_id' => $usePromo ? $promoId : null,
            ];
            if ($payment !== null) {
                $data['initial_payment'] = ['jumlah' => $payment, 'metode' => 'tunai'];
            }
            $transactionId = app(TransactionService::class)->create($owner, $data);
            $target = $firstTen ? 'SUDAH_DIAMBIL' : match ($index) {
                10 => 'DITERIMA', 11 => 'DIPROSES', 12, 13 => 'SIAP_DIAMBIL', 14 => 'DIBATALKAN',
            };
            if (in_array($target, ['DIPROSES', 'SIAP_DIAMBIL', 'SUDAH_DIAMBIL'], true)) {
                $this->move($owner, $transactionId, 'DIPROSES');
            }
            if (in_array($target, ['SIAP_DIAMBIL', 'SUDAH_DIAMBIL'], true)) {
                $this->move($owner, $transactionId, 'SIAP_DIAMBIL');
            }
            if ($target === 'SUDAH_DIAMBIL') {
                $this->move($owner, $transactionId, 'SUDAH_DIAMBIL');
            }
            if ($target === 'DIBATALKAN') {
                $version = (int) DB::table('transactions')->where('id', $transactionId)->value('version');
                app(CancellationService::class)->cancel($owner, $transactionId, $version, 'Contoh transaksi dibatalkan');
            }
            $base = match ($index) {
                12 => $at->subDays(3)->subHours(2),
                13 => $at->subDays(5)->subHours(2),
                default => $at->subDays(20 - $index),
            };
            $this->backdate($transactionId, $base);
        }

        return $admin;
    }

    private function move(User $owner, int $transactionId, string $target): void
    {
        $version = (int) DB::table('transactions')->where('id', $transactionId)->value('version');
        app(TransactionStateMachine::class)->move($owner, $transactionId, $target, $version);
    }

    private function backdate(int $transactionId, CarbonImmutable $base): void
    {
        $tx = DB::table('transactions')->where('id', $transactionId)->first();
        $ready = in_array($tx->status, ['SIAP_DIAMBIL', 'SUDAH_DIAMBIL'], true) ? $base->addHours(2) : null;
        DB::table('transactions')->where('id', $transactionId)->update([
            'waktu_masuk' => $base, 'estimasi_selesai' => $base->addHours(48),
            'waktu_siap_diambil' => $ready,
            'waktu_diambil' => $tx->status === 'SUDAH_DIAMBIL' ? $base->addHours(3) : null,
            'created_at' => $base, 'updated_at' => $base->addHours(4),
        ]);
        foreach (DB::table('status_histories')->where('transaction_id', $transactionId)->orderBy('id')->pluck('id') as $offset => $id) {
            DB::table('status_histories')->where('id', $id)->update(['created_at' => $base->addHours($offset)]);
        }
        DB::table('payments')->where('transaction_id', $transactionId)->update(['waktu' => $base->addMinutes(30), 'created_at' => $base->addMinutes(30)]);
        foreach (DB::table('loyalty_histories')->where('transaction_id', $transactionId)->orderBy('id')->pluck('id') as $offset => $id) {
            DB::table('loyalty_histories')->where('id', $id)->update(['created_at' => $base->addMinutes(40 + $offset)]);
        }
        DB::table('notification_logs')->where('transaction_id', $transactionId)->update(['created_at' => $ready ?? $base, 'updated_at' => $ready ?? $base]);
    }
}

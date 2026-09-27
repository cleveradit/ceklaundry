<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AccountService;
use Illuminate\Database\Seeder;
use RuntimeException;

class DeveloperSeeder extends Seeder
{
    public function run(): void
    {
        $data = ['nama' => getenv('CEKLAUNDRY_BOOTSTRAP_NAME'), 'email' => getenv('CEKLAUNDRY_BOOTSTRAP_EMAIL'), 'password' => getenv('CEKLAUNDRY_BOOTSTRAP_PASSWORD')];
        if (in_array(false, $data, true)) {
            throw new RuntimeException('Injeksi rahasia bootstrap belum lengkap. Gunakan command interaktif.');
        }
        $valid = app(AccountService::class)->validateAccount($data);
        (new User)->forceFill([...$valid, 'role' => 'developer', 'is_active' => true, 'must_change_password' => true])->save();
    }
}

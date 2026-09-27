<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountService;
use Illuminate\Console\Command;

class BootstrapDeveloper extends Command
{
    protected $signature = 'app:bootstrap-developer';

    protected $description = 'Membuat akun developer tanpa password bawaan.';

    public function handle(AccountService $accounts): int
    {
        $data = $accounts->validateAccount(['nama' => $this->ask('Nama developer'), 'email' => $this->ask('Email developer'), 'password' => $this->secret('Password awal (minimal 12 karakter, maksimal 72 byte)')]);
        (new User)->forceFill([...$data, 'role' => 'developer', 'is_active' => true, 'must_change_password' => true])->save();
        $this->info('Akun developer dibuat. Ganti password saat pertama masuk.');

        return self::SUCCESS;
    }
}

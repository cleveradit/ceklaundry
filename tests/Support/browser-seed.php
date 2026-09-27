<?php

use App\Models\User;
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
$email = 'browser-'.Str::lower(Str::random(8)).'@example.test';
(new User)->forceFill(['nama' => 'Developer QA', 'email' => $email, 'password' => $password, 'role' => 'developer', 'is_active' => true, 'must_change_password' => true])->save();
$path = storage_path('app/private/browser-fixture.json');
file_put_contents($path, json_encode(['email' => $email, 'password' => $password]));
chmod($path, 0600);
echo "Isolated browser fixture ready.\n";

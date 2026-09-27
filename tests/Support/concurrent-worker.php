<?php

use App\Models\User;
use App\Services\AuthRateLimiter;
use App\Services\BranchService;
use App\Services\BusinessTransaction;
use App\Services\MasterSyncService;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.connections.mysql.database') !== 'ceklaundry_test') {
    exit(90);
}
$args = json_decode($argv[1], true, 512, JSON_THROW_ON_ERROR);
$actor = User::findOrFail($args['actor']);
// Signal after loading the intentionally stale actor, before attempting the root lock.
file_put_contents($args['barrier'], 'ready');
try {
    if ($args['operation'] === 'limiter') {
        Carbon::setTestNow($args['now']);
        app(AuthRateLimiter::class)->attempt('login', 'parallel@example.test', '192.0.2.55');
    } elseif ($args['operation'] === 'branch') {
        app(BranchService::class)->save($actor, ['nama' => 'Paralel', 'alamat' => 'Jalan Uji', 'telepon' => '081234567890', 'is_active' => true]);
    } elseif ($args['operation'] === 'deactivate') {
        app(BranchService::class)->save($actor, ['nama' => 'Utama', 'alamat' => 'Jalan Uji', 'telepon' => '081234567890', 'is_active' => false], $args['branch']);
    } elseif ($args['operation'] === 'sync') {
        app(MasterSyncService::class)->apply($actor, $args['branches'], $args['fingerprint']);
    } else {
        app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, $fresh) use ($args) {
            abort_unless($fresh->branch_id === $args['branch'], 404);
            DB::table('branches')->where('id', $args['branch'])->update(['alamat' => 'ILLEGAL']);
        });
    }
    echo json_encode(['status' => 200]);
} catch (ValidationException $e) {
    echo json_encode(['status' => 422]);
} catch (HttpExceptionInterface $e) {
    echo json_encode(['status' => $e->getStatusCode()]);
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e).': '.$e->getMessage());
    exit(1);
}

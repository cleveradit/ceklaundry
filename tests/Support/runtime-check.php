<?php

use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Cache;
use Tests\Support\QueueProbe;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if ($app->environment('production')) {
    throw new LogicException('Probe hanya untuk runtime lokal/CI.');
}
$nonce = $argv[2] ?? 'm1-runtime-check';
if (($argv[1] ?? '') === 'dispatch') {
    Cache::forget('qa:queue:'.$nonce);
    QueueProbe::dispatch($nonce);
    echo "Probe queued.\n";
} else {
    $worker = Cache::pull('qa:queue:'.$nonce);
    $heartbeat = Cache::get('scheduler:heartbeat');
    if (! $worker || ! $heartbeat || Carbon::parse($heartbeat)->lt(now()->subMinutes(3))) {
        throw new RuntimeException('Worker atau heartbeat scheduler belum terverifikasi.');
    }
    echo "PASS: database worker and cron heartbeat.\n";
}

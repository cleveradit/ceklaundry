<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

abstract class ConcurrentTestCase extends FoundationTestCase
{
    private array $children = [];

    public function refreshDatabase(): void
    {
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
    }

    protected function startWorker(array $args): Process
    {
        $barrier = tempnam(sys_get_temp_dir(), 'm1-barrier-');
        $process = new Process([PHP_BINARY, base_path('tests/Support/concurrent-worker.php'), json_encode([...$args, 'barrier' => $barrier])], base_path(), ['APP_ENV' => 'testing', 'DB_DATABASE' => 'ceklaundry_test', 'BCRYPT_ROUNDS' => '4']);
        $process->setTimeout(15);
        $process->start();
        $this->children[] = [$process, $barrier];
        $deadline = microtime(true) + 10;
        while (file_get_contents($barrier) !== 'ready' && microtime(true) < $deadline && $process->isRunning()) {
            usleep(10000);
        }
        $this->assertSame('ready', file_get_contents($barrier), $process->getErrorOutput());

        return $process;
    }

    protected function assertBlocked(Process $process): void
    {
        usleep(150000);
        $this->assertTrue($process->isRunning(), $process->getOutput().$process->getErrorOutput());
        $this->assertSame('', $process->getOutput());
    }

    protected function workerResult(Process $process, int $status): void
    {
        $process->wait();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertSame($status, json_decode($process->getOutput(), true)['status']);
    }

    protected function tearDown(): void
    {
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($this->children as [$process, $barrier]) {
            if ($process->isRunning()) {
                $process->stop();
            } @unlink($barrier);
        }
        // Keep subsequent RefreshDatabase tests empty without wrapping these races in a transaction.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (DB::select('SHOW TABLES') as $row) {
            $table = array_values((array) $row)[0];
            if ($table !== 'migrations') {
                DB::statement('TRUNCATE TABLE `'.$table.'`');
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }
}

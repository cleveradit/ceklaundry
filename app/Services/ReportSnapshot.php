<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

class ReportSnapshot
{
    public function read(callable $callback): mixed
    {
        // A separate connection keeps report isolation independent of write transactions.
        config(['database.connections.owner_reports' => [
            ...config('database.connections.mysql'), 'isolation_level' => 'REPEATABLE READ',
        ]]);
        $db = DB::connection('owner_reports');
        try {
            $db->statement('SET TRANSACTION READ ONLY');

            return $db->transaction(fn (Connection $connection) => $callback($connection));
        } finally {
            DB::purge('owner_reports');
        }
    }
}

<?php

namespace Tests\Feature\Foundation;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RuntimeSmokeTest extends TestCase
{
    public function test_public_page_is_blade_without_panel_bundle(): void
    {
        $this->withoutVite()->get('/')
            ->assertOk()->assertSee('CekLaundry')->assertSee('Pengecekan resi segera hadir')
            ->assertDontSee('data-page')->assertDontSee('app.tsx')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_runtime_uses_mysql_wib_and_read_committed(): void
    {
        $row = DB::selectOne('SELECT VERSION() AS version, @@session.time_zone AS timezone, @@session.transaction_isolation AS isolation_level');
        $this->assertStringStartsWith('8.4.', $row->version);
        $this->assertSame('+07:00', $row->timezone);
        $this->assertSame('READ-COMMITTED', $row->isolation_level);
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertFalse(config('queue.connections.database.after_commit'));
    }
}

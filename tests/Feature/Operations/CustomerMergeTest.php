<?php

namespace Tests\Feature\Operations;

use App\Models\User;
use App\Services\CustomerMergeService;
use App\Services\CustomerService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\FoundationTestCase;

class CustomerMergeTest extends FoundationTestCase
{
    public function test_owner_merges_identity_and_admin_cannot_cross_branch(): void
    {
        [$business, $owner] = $this->tenant();
        $first = $this->branch($owner);
        $second = $this->branch($owner, 'Cabang Kedua');
        $source = app(CustomerService::class)->save($owner, ['nama' => 'Sumber', 'no_hp' => '081355500001']);
        $target = app(CustomerService::class)->save($owner, ['nama' => 'Tujuan', 'no_hp' => '081355500002']);
        $tx1 = $this->transaction($business, $owner, $first->id, ['customer_id' => $source]);
        $tx2 = $this->transaction($business, $owner, $second->id, ['customer_id' => $target]);
        $admin = User::factory()->create(['business_id' => $business->id, 'branch_id' => $first->id, 'role' => 'admin', 'must_change_password' => false]);
        try {
            app(CustomerMergeService::class)->merge($admin, $source, $target);
            $this->fail('Admin lintas cabang dapat merge.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        app(CustomerMergeService::class)->merge($owner, $source, $target);
        $this->assertNull(DB::table('customers')->find($source));
        $this->assertSame($target, DB::table('transactions')->find($tx1)->customer_id);
        $this->assertSame($target, DB::table('transactions')->find($tx2)->customer_id);
        $this->assertSame('Tujuan', DB::table('customers')->find($target)->nama);
        $this->assertSame(1, DB::table('audit_logs')->where('aksi', 'pelanggan.gabung')->count());
    }
}

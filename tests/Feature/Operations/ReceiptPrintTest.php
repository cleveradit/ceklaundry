<?php

namespace Tests\Feature\Operations;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\FoundationTestCase;

class ReceiptPrintTest extends FoundationTestCase
{
    public function test_internal_print_remains_available_in_read_only_and_is_branch_scoped(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $other = $this->branch($owner, 'Cabang Kedua');
        $id = $this->transaction($business, $owner, $branch->id, ['kode_resi' => 'ABC234']);
        $code = DB::table('transactions')->find($id)->kode_resi;
        $admin = User::factory()->create(['business_id' => $business->id, 'branch_id' => $other->id, 'role' => 'admin']);

        $this->actingAs($admin)->get('/app/transactions/'.$id.'/print')->assertNotFound();
        $this->actingAs($owner)->get('/app/transactions/'.$id.'/print')->assertOk()->assertSee('Pelanggan Uji');

        $business->forceFill(['active_until' => now()->subDays(8)])->save();
        $this->get('/t/'.$code.'/print')->assertOk()->assertDontSee('Pelanggan Uji');
        $this->actingAs($owner)->get('/app/transactions/'.$id.'/print')->assertOk()->assertSee('Pelanggan Uji');
    }
}

<?php

namespace Tests\Feature\Operations;

use App\Models\User;
use App\Services\PricingService;
use App\Services\ServiceCatalogService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\FoundationTestCase;

class OwnerOperationsTest extends FoundationTestCase
{
    public function test_owner_can_create_in_own_branch_and_developer_cannot_read_detail(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $service = app(ServiceCatalogService::class)->save($owner, ['nama' => 'Cuci', 'satuan' => 'item', 'harga' => 10000, 'durasi_jam' => 24, 'berat_minimum' => null, 'is_active' => true], null, $branch->id);
        $items = [['service_id' => $service->id, 'jumlah_unit' => 1]];
        $quote = app(PricingService::class)->quote($business->id, $branch->id, $items);
        $id = app(TransactionService::class)->create($owner, ['branch_id' => $branch->id, 'customer' => ['nama' => 'Rani', 'no_hp' => '081398765432'], 'items' => $items, 'request_key' => (string) Str::uuid(), 'quote_fingerprint' => $quote['fingerprint']]);
        $this->assertSame($owner->id, DB::table('transactions')->find($id)->created_by);
        $this->actingAs($owner)->get('/app/transactions/'.$id)->assertOk();
        $developer = User::factory()->create();
        $this->actingAs($developer)->get('/app/transactions/'.$id)->assertForbidden();
    }
}

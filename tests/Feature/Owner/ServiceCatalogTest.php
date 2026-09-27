<?php

namespace Tests\Feature\Owner;

use App\Models\MasterService;
use App\Services\MasterSyncService;
use App\Services\ServiceCatalogService;
use Illuminate\Support\Facades\DB;
use Tests\FoundationTestCase;

class ServiceCatalogTest extends FoundationTestCase
{
    public function test_normalized_unique_case_insensitive_accent_sensitive_and_tenant_scoped(): void
    {
        [$a, $ownerA] = $this->tenant();
        [$b, $ownerB] = $this->tenant();
        $service = app(ServiceCatalogService::class);
        $data = ['nama' => "\u{00A0}Cuci \t Setrika\u{00A0}", 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 24, 'berat_minimum' => 3, 'is_active' => true];
        $master = $service->save($ownerA, $data);
        $this->assertSame('Cuci Setrika', $master->nama);
        $this->actingAs($ownerA)->post('/owner/masters', [...$data, 'nama' => 'cuci setrika'])->assertSessionHasErrors('nama');
        $service->save($ownerB, $data);
        $service->save($ownerA, [...$data, 'nama' => 'Cúci Setrika']);
        $this->assertSame(2, $this->inTenant($a, fn () => MasterService::count()));
    }

    public function test_numeric_boundaries_and_item_minimum(): void
    {
        [$a, $owner] = $this->tenant();
        $data = ['nama' => 'Batas', 'satuan' => 'kg', 'harga' => 4294967295, 'durasi_jam' => 65535, 'berat_minimum' => 9999.9, 'is_active' => true];
        $this->actingAs($owner)->post('/owner/masters', $data)->assertSessionHasNoErrors();
        foreach (['harga' => 4294967296, 'durasi_jam' => 0, 'berat_minimum' => 0.01] as $field => $value) {
            $this->post('/owner/masters', [...$data, 'nama' => 'Lain', $field => $value])->assertSessionHasErrors($field);
        }
        $this->post('/owner/masters', [...$data, 'nama' => 'Item', 'satuan' => 'item'])->assertSessionHasErrors('berat_minimum');
    }

    public function test_master_edit_does_not_update_local_or_transaction_snapshot_and_guards_reward(): void
    {
        [$business, $owner] = $this->tenant();
        $branch = $this->branch($owner);
        $catalog = app(ServiceCatalogService::class);
        $data = ['nama' => 'Cuci', 'satuan' => 'kg', 'harga' => 7000, 'durasi_jam' => 24, 'is_active' => true];
        $master = $catalog->save($owner, $data);
        $local = $catalog->save($owner, $data, null, $branch->id);
        $tx = $this->transaction($business, $owner, $branch->id);
        DB::table('transaction_items')->insert(['transaction_id' => $tx, 'service_id' => $local->id, 'nama_layanan_snapshot' => 'Cuci', 'satuan_snapshot' => 'kg', 'harga_snapshot' => 7000, 'durasi_jam_snapshot' => 24, 'berat_kg' => 1, 'subtotal' => 7000]);
        $catalog->save($owner, [...$data, 'harga' => 9000], $master->id);
        $this->assertSame(7000, DB::table('services')->value('harga'));
        $catalog->save($owner, [...$data, 'harga' => 8500], $local->id, $branch->id);
        $this->assertSame(7000, DB::table('transaction_items')->value('harga_snapshot'));
        $sync = app(MasterSyncService::class);
        $preview = $sync->preview($owner, [$branch->id]);
        $sync->apply($owner, [$branch->id], $preview['fingerprint']);
        $this->assertSame(9000, DB::table('services')->value('harga'));
        $this->assertSame(7000, DB::table('transaction_items')->value('harga_snapshot'));
        DB::table('loyalty_settings')->where('business_id', $business->id)->update(['is_active' => true, 'master_service_id' => $master->id, 'berat_maks_gratis' => 3]);
        $this->actingAs($owner)->put('/owner/masters/'.$master->id, [...$data, 'is_active' => false])->assertSessionHasErrors('is_active');
        $this->put('/owner/masters/'.$master->id, [...$data, 'satuan' => 'item'])->assertSessionHasErrors('is_active');
    }
}

<?php

namespace Tests\Feature\Tenancy;

use App\Jobs\Middleware\UseTenantContext;
use App\Models\Branch;
use App\Models\Service;
use App\Services\AccountService;
use App\Services\ServiceCatalogService;
use App\Tenancy\TenantContext;
use Tests\FoundationTestCase;

class TenantIsolationTest extends FoundationTestCase
{
    public function test_cross_tenant_resource_is_404_and_wrong_role_is_403(): void
    {
        [$a, $ownerA] = $this->tenant();
        [$b, $ownerB] = $this->tenant();
        $branch = $this->branch($ownerB);
        $this->actingAs($ownerA)->put('/owner/branches/'.$branch->id, ['nama' => 'Hijack'])->assertNotFound();
        $this->actingAs($ownerA)->get('/dev')->assertForbidden();
        $this->actingAs($this->developer())->get('/owner/branches')->assertForbidden();
        $this->assertSame('Cabang Utama', $this->inTenant($b, fn () => Branch::find($branch->id)->nama));
    }

    public function test_admin_services_are_scoped_to_current_branch(): void
    {
        [$business, $owner] = $this->tenant();
        $one = $this->branch($owner);
        $two = $this->branch($owner, 'Cabang Kedua');
        $admin = app(AccountService::class)->saveAdmin($owner, ['nama' => 'Admin', 'email' => 'admin@example.test', 'password' => $this->password(), 'branch_id' => $one->id, 'is_active' => true]);
        $admin->forceFill(['must_change_password' => false])->save();
        foreach ([$one, $two] as $branch) {
            app(ServiceCatalogService::class)->save($owner, ['nama' => $branch->nama, 'harga' => 7000, 'satuan' => 'kg', 'durasi_jam' => 24, 'is_active' => true], null, $branch->id);
        }
        app(TenantContext::class)->run($business->id, function () use ($one) {
            $this->assertSame([$one->id], Service::query()->pluck('branch_id')->all());
        }, $admin);
        $this->actingAs($admin)->get('/owner/admins')->assertForbidden();
        $this->actingAs($admin)->get('/app')->assertOk();
    }

    public function test_context_missing_fails_closed(): void
    {
        $this->expectException(\LogicException::class);
        Branch::query()->get();
    }

    public function test_worker_context_cleared_after_exception_and_between_tenants(): void
    {
        [$a, $ownerA] = $this->tenant();
        [$b, $ownerB] = $this->tenant();
        $this->branch($ownerA);
        $this->branch($ownerB);
        $middleware = new UseTenantContext;
        try {
            $middleware->handle((object) ['businessId' => $a->id], function () use ($a) {
                $this->assertSame($a->id, Branch::first()->business_id);
                throw new \RuntimeException('fixture');
            });
        } catch (\RuntimeException) {
        }
        $this->assertNull(app(TenantContext::class)->businessId);
        $middleware->handle((object) ['businessId' => $b->id], function () use ($b) {
            $this->assertSame($b->id, Branch::first()->business_id);
        });
        $this->assertNull(app(TenantContext::class)->businessId);
        $called = false;
        $middleware->handle((object) [], function () use (&$called) {
            $called = true;
        });
        $this->assertFalse($called);
    }
}

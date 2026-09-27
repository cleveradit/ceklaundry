<?php

namespace App\Providers;

use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn () => new TenantContext);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Queue::createPayloadUsing(fn () => ['business_id' => app(TenantContext::class)->businessId]);
        Queue::before(fn () => app(TenantContext::class)->clear());
        Queue::after(fn () => app(TenantContext::class)->clear());
        Queue::exceptionOccurred(fn () => app(TenantContext::class)->clear());
    }
}

<?php

namespace App\Jobs\Middleware;

use App\Models\Business;
use App\Models\NotificationLog;
use App\Tenancy\TenantContext;

class UseTenantContext
{
    public function handle(object $job, callable $next): void
    {
        $context = app(TenantContext::class);
        $context->clear();
        try {
            $id = $job->businessId ?? null;
            if (! is_int($id) || ! Business::query()->whereKey($id)->exists()) {
                return;
            }
            $context->set($id);
            if (isset($job->logId) && ! NotificationLog::query()->whereKey($job->logId)->exists()) {
                return;
            }
            $next($job);
        } finally {
            $context->clear();
        }
    }
}

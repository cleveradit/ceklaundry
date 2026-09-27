<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class LifecycleService
{
    public function update(User $actor, int $id, array $data): void
    {
        $valid = Validator::make($data, ['active_until' => ['required', 'date_format:Y-m-d'], 'is_active' => ['required', 'boolean']])->validate();
        app(BusinessTransaction::class)->run($actor, $id, function (Business $business, User $fresh) use ($valid) {
            Gate::forUser($fresh)->authorize('update', $business);
            $before = ['active_until' => $business->active_until?->format('Y-m-d'), 'is_active' => $business->is_active];
            $wasWritable = $this->writable($business);
            $business->forceFill($valid);
            if (! $wasWritable || ! $this->writable($business)) {
                app(PendingNotificationInvalidator::class)->invalidate('tenant_readonly');
            }
            $business->save();
            Audit::record($fresh, $business->id, 'bisnis.lifecycle', ['before' => $before, 'after' => $valid]);
        }, 'administration');
    }

    public function status(Business $business): string
    {
        if (! $business->is_active) {
            return 'NONAKTIF';
        }
        if ($business->is_demo) {
            return now()->greaterThanOrEqualTo($business->demo_expires_at) ? 'DEMO_EXPIRED' : 'DEMO';
        }
        $today = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
        if ($today->lessThanOrEqualTo($business->active_until)) {
            return 'AKTIF';
        }

        return $today->lessThanOrEqualTo($business->active_until->addDays(7)) ? 'TENGGANG' : 'BACA_SAJA';
    }

    public function writable(Business $business): bool
    {
        return in_array($this->status($business), ['AKTIF', 'TENGGANG', 'DEMO'], true);
    }

    public function panelAllowed(Business $business): bool
    {
        return ! in_array($this->status($business), ['NONAKTIF', 'DEMO_EXPIRED'], true);
    }

    public function publicAllowed(Business $business): bool
    {
        return ! $business->is_demo || ($business->is_active && now()->lessThan($business->demo_expires_at));
    }

    public function warning(Business $business): bool
    {
        return ! $business->is_demo && $business->active_until && now('Asia/Jakarta')->startOfDay()->greaterThanOrEqualTo($business->active_until->subDays(7));
    }
}

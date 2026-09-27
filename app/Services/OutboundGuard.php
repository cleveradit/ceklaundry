<?php

namespace App\Services;

use App\Models\Business;
use Carbon\CarbonImmutable;
use Throwable;

class OutboundGuard
{
    public function allows(?Business $business, mixed $requestedAt = null): bool
    {
        $hold = filter_var(config('outbound.hold'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($hold !== false || $business?->is_demo) {
            return false;
        }
        $cutoff = config('outbound.resume_at');
        if ($cutoff === null || $cutoff === '') {
            return true;
        }
        try {
            $parsed = CarbonImmutable::createFromFormat('!Y-m-d H:i:s', $cutoff, 'Asia/Jakarta');
            if (! $parsed || $parsed->format('Y-m-d H:i:s') !== $cutoff || ! $requestedAt) {
                return false;
            }

            return CarbonImmutable::parse($requestedAt, 'Asia/Jakarta')->greaterThan($parsed);
        } catch (Throwable) {
            return false;
        }
    }
}

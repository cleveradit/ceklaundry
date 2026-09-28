<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class EstimationService
{
    public function calculate(CarbonImmutable $incoming, array $items, ?string $manual = null): CarbonImmutable
    {
        try {
            $estimated = $manual === null || $manual === ''
                ? $incoming->addHours(max(array_column($items, 'durasi_jam_snapshot')))
                : CarbonImmutable::parse($manual, 'Asia/Jakarta');
        } catch (\Throwable) {
            throw ValidationException::withMessages(['estimasi_selesai' => 'Estimasi selesai tidak valid.']);
        }
        if ($estimated->lessThan($incoming)) {
            throw ValidationException::withMessages(['estimasi_selesai' => 'Estimasi tidak boleh sebelum waktu masuk.']);
        }

        return $estimated;
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReceiptRateLimiter
{
    public function check(Request $request): void
    {
        $window = intdiv(now('Asia/Jakarta')->timestamp, 60);
        $key = 'receipt:'.hash('sha256', (string) $request->ip()).':'.$window;
        Cache::add($key, 0, 120);
        $count = Cache::increment($key);
        abort_if($count > 30, 429, 'Terlalu banyak percobaan. Coba lagi sebentar.');
    }
}

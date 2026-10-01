<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DemoRateLimiter
{
    public function check(Request $request): void
    {
        $fingerprint = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $key = 'demo:ip:'.$fingerprint.':'.now('Asia/Jakarta')->format('Ymd');
        Cache::lock('demo:lock:'.$fingerprint, 10)->block(10, function () use ($key) {
            abort_if((int) Cache::get($key, 0) >= 3, 429, 'Batas tiga demo per hari tercapai. Coba lagi besok.');
            Cache::add($key, 0, 90000);
            Cache::increment($key);
        });
    }
}

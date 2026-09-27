<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AuthRateLimiter
{
    public function attempt(string $kind, string $email, string $ip): void
    {
        $hour = $kind === 'reset';
        $window = now('Asia/Jakarta')->format($hour ? 'YmdH' : 'YmdHi');
        $expires = $hour ? now()->endOfHour()->addSecond() : now()->endOfMinute()->addSecond();
        $identity = hash_hmac('sha256', $email.($hour ? '' : '|'.$ip), config('app.key'));
        $ipHash = hash_hmac('sha256', $ip, config('app.key'));
        $keys = ["auth:$kind:$window:identity:$identity" => $hour ? 3 : 5, "auth:$kind:$window:ip:$ipHash" => $hour ? 10 : 30];
        // One infrastructure lock makes both fixed-window counters a single decision.
        Cache::lock('auth-limit:'.$kind, 10)->block(5, function () use ($keys, $expires) {
            $limited = false;
            foreach ($keys as $key => $limit) {
                $count = (int) Cache::get($key, 0);
                $limited = $limited || $count >= $limit;
                Cache::put($key, $count + 1, $expires);
            }
            abort_if($limited, 429, 'Terlalu banyak percobaan. Silakan coba lagi nanti.');
        });
    }
}

<?php

namespace Tests\Support;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class QueueProbe implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $nonce) {}

    public function handle(): void
    {
        Cache::put('qa:queue:'.$this->nonce, true, 300);
    }
}

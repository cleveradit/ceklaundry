<?php

namespace App\Support;

class ServiceName
{
    public static function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value));
    }
}

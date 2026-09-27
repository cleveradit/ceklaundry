<?php

namespace App\Support;

class ServiceName
{
    public static function normalize(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value));
    }
}

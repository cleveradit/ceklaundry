<?php

namespace App\Support;

use Closure;

class Input
{
    public static function email(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    public static function passwordRules(): array
    {
        return ['required', 'string', 'min:12', function (string $attribute, mixed $value, Closure $fail) {
            if (strlen($value) > 72) {
                $fail('Password maksimal 72 byte UTF-8.');
            }
        }];
    }

    public static function phone(string $value): string
    {
        $value = preg_replace('/[\s()\-]+/u', '', $value);
        if (str_starts_with($value, '+62')) {
            $value = substr($value, 1);
        }
        if (str_starts_with($value, '08')) {
            $value = '62'.substr($value, 1);
        }

        return $value;
    }
}

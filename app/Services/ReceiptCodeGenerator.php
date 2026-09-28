<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReceiptCodeGenerator
{
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function generate(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            if (! DB::table('transactions')->where('kode_resi', $code)->exists()) {
                return $code;
            }
        }
        throw new RuntimeException('Kode resi belum dapat dibuat. Coba lagi.');
    }
}

<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class StaleQuoteException extends HttpException
{
    public function __construct(public readonly array $quote)
    {
        parent::__construct(409, 'Harga berubah. Periksa penawaran baru.');
    }
}

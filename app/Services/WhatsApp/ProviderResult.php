<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\Response;

class ProviderResult
{
    public static function from(Response $response, bool $accepted): array
    {
        if ($accepted) {
            return ['outcome' => 'accepted', 'code' => null];
        }
        if ($response->status() >= 400 && $response->status() < 500) {
            return ['outcome' => 'definitely_rejected', 'code' => 'provider_4xx'];
        }
        if ($response->successful()) {
            $body = $response->json();
            if (is_array($body) && ($body['status'] ?? null) === false) {
                return ['outcome' => 'definitely_rejected', 'code' => 'provider_menolak'];
            }
        }

        return ['outcome' => 'unknown', 'code' => 'provider_tidak_pasti'];
    }
}

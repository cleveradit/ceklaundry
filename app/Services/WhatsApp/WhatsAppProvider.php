<?php

namespace App\Services\WhatsApp;

interface WhatsAppProvider
{
    /** @return array{outcome: string, code: string|null} */
    public function send(string $recipient, array $payload, string $token, string $type): array;
}

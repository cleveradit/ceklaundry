<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Throwable;

class WablasProvider implements WhatsAppProvider
{
    public function send(string $recipient, array $payload, string $token, string $type): array
    {
        $options = $payload['provider_options'] ?? [];
        $base = rtrim((string) ($options['base_url'] ?? ''), '/');
        $host = parse_url($base, PHP_URL_HOST);
        if (! str_starts_with($base, 'https://') || ! is_string($host) ||
            ($host !== 'wablas.com' && ! str_ends_with($host, '.wablas.com')) || empty($options['secret_key'])) {
            return ['outcome' => 'definitely_rejected', 'code' => 'wablas_config'];
        }
        try {
            $response = Http::connectTimeout(5)->timeout(30)->withoutRedirecting()
                ->withHeaders(['Authorization' => $token.'.'.$options['secret_key']])->asForm()
                ->post($base.'/api/send-message', ['phone' => $recipient, 'message' => FonnteProvider::message($payload, $type)]);
            $body = $response->json();

            return ProviderResult::from($response, $response->successful() && is_array($body) && ($body['status'] ?? false) === true &&
                ! empty($body['data']['messages']));
        } catch (Throwable) {
            return ['outcome' => 'unknown', 'code' => 'wablas_transport'];
        }
    }
}

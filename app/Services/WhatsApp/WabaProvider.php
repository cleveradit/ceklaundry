<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Throwable;

class WabaProvider implements WhatsAppProvider
{
    public function send(string $recipient, array $payload, string $token, string $type): array
    {
        $options = $payload['provider_options'] ?? [];
        $template = $type === 'pengingat' ? ($options['reminder_template_name'] ?? null) : ($options['ready_template_name'] ?? null);
        if (! preg_match('/^v[0-9]+\.[0-9]+$/D', (string) ($options['api_version'] ?? '')) ||
            ! ctype_digit((string) ($options['phone_number_id'] ?? '')) || ! $template || empty($options['language_code'])) {
            return ['outcome' => 'definitely_rejected', 'code' => 'waba_config'];
        }
        $values = [$payload['branch'], $payload['code'], 'Rp'.number_format($payload['total'], 0, ',', '.'),
            'Rp'.number_format($payload['remaining'], 0, ',', '.'), $payload['url']];
        try {
            $response = Http::connectTimeout(5)->timeout(30)->withoutRedirecting()->withToken($token)
                ->post('https://graph.facebook.com/'.$options['api_version'].'/'.$options['phone_number_id'].'/messages', [
                    'messaging_product' => 'whatsapp', 'to' => $recipient, 'type' => 'template',
                    'template' => ['name' => $template, 'language' => ['code' => $options['language_code']],
                        'components' => [['type' => 'body', 'parameters' => array_map(fn ($value) => ['type' => 'text', 'text' => $value], $values)]],
                    ],
                ]);
            $body = $response->json();

            return ProviderResult::from($response, $response->successful() && is_array($body) && ! empty($body['messages'][0]['id']));
        } catch (Throwable) {
            return ['outcome' => 'unknown', 'code' => 'waba_transport'];
        }
    }
}

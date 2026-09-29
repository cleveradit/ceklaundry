<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Throwable;

class FonnteProvider implements WhatsAppProvider
{
    public function send(string $recipient, array $payload, string $token, string $type): array
    {
        try {
            $response = Http::connectTimeout(5)->timeout(30)->withoutRedirecting()
                ->withHeaders(['Authorization' => $token])->asForm()->post('https://api.fonnte.com/send', [
                    'target' => $recipient, 'message' => self::message($payload, $type),
                ]);
            $body = $response->json();

            return ProviderResult::from($response, $response->successful() && is_array($body) && ($body['status'] ?? false) === true);
        } catch (Throwable) {
            return ['outcome' => 'unknown', 'code' => 'fonnte_transport'];
        }
    }

    public static function message(array $data, string $type): string
    {
        return ($type === 'pengingat' ? 'Pengingat: ' : '')."Cucian {$data['code']} di {$data['branch']} siap diambil. "
            .'Total Rp'.number_format($data['total'], 0, ',', '.').', sisa Rp'.number_format($data['remaining'], 0, ',', '.').'. '.$data['url'];
    }
}

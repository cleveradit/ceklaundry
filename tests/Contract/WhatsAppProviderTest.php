<?php

namespace Tests\Contract;

use App\Services\WhatsApp\FonnteProvider;
use App\Services\WhatsApp\WabaProvider;
use App\Services\WhatsApp\WablasProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppProviderTest extends TestCase
{
    private function payload(): array
    {
        return ['branch' => 'Cabang Utama', 'code' => 'ABC234', 'total' => 74500, 'remaining' => 44500,
            'url' => 'https://example.test/t/ABC234', 'provider_options' => []];
    }

    public function test_fonnte_requires_body_acceptance(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::sequence()->push(['status' => false, 'reason' => 'invalid'], 200)
            ->push(['status' => true, 'id' => ['123']], 200)->push(['status' => true], 503)]);
        $provider = app(FonnteProvider::class);
        $this->assertSame('definitely_rejected', $provider->send('6281234567890', $this->payload(), 'token', 'siap_diambil')['outcome']);
        $this->assertSame('accepted', $provider->send('6281234567890', $this->payload(), 'token', 'siap_diambil')['outcome']);
        $this->assertSame('unknown', $provider->send('6281234567890', $this->payload(), 'token', 'siap_diambil')['outcome']);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.fonnte.com/send' && $request->hasHeader('Authorization', 'token'));
    }

    public function test_wablas_uses_authorization_and_requires_message_id(): void
    {
        Http::fake(['*.wablas.com/*' => Http::sequence()->push(['status' => true, 'data' => ['messages' => []]], 200)
            ->push(['status' => true, 'data' => ['messages' => [['id' => 'abc']]]], 200)]);
        $payload = $this->payload();
        $payload['provider_options'] = ['base_url' => 'https://solo.wablas.com', 'secret_key' => 'secret'];
        $provider = app(WablasProvider::class);
        $this->assertSame('unknown', $provider->send('6281234567890', $payload, 'token', 'pengingat')['outcome']);
        $this->assertSame('accepted', $provider->send('6281234567890', $payload, 'token', 'pengingat')['outcome']);
        Http::assertSent(fn ($request) => $request->url() === 'https://solo.wablas.com/api/send-message' &&
            $request->hasHeader('Authorization', 'token.secret'));
    }

    public function test_waba_uses_five_template_parameters_and_message_id(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.123']]], 200)]);
        $payload = $this->payload();
        $payload['provider_options'] = ['api_version' => 'v22.0', 'phone_number_id' => '123456',
            'ready_template_name' => 'laundry_ready', 'reminder_template_name' => 'laundry_reminder', 'language_code' => 'id'];
        $this->assertSame('accepted', app(WabaProvider::class)->send('6281234567890', $payload, 'token', 'siap_diambil')['outcome']);
        Http::assertSent(function ($request) {
            $parameters = $request['template']['components'][0]['parameters'] ?? [];

            return $request->url() === 'https://graph.facebook.com/v22.0/123456/messages' && count($parameters) === 5 &&
                $parameters[0]['text'] === 'Cabang Utama' && $parameters[1]['text'] === 'ABC234';
        });
    }
}

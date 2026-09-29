<?php

namespace App\Services;

use App\Services\WhatsApp\FonnteProvider;
use App\Services\WhatsApp\WabaProvider;
use App\Services\WhatsApp\WablasProvider;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

class NotificationTransport
{
    public function send(array $envelope): array
    {
        $business = $envelope['business'];
        if (! app(OutboundGuard::class)->allows($business, $envelope['created_at']) ||
            (! $envelope['is_manual'] && $envelope['type'] !== 'verifikasi_email' && ! app(OutboundGuard::class)->allows($business, $envelope['ready_at']))) {
            return ['outcome' => 'unknown', 'code' => 'transport_ditahan'];
        }
        if ($envelope['channel'] === 'whatsapp') {
            $provider = match ($envelope['provider']) {
                'fonnte' => app(FonnteProvider::class), 'wablas' => app(WablasProvider::class), 'waba' => app(WabaProvider::class),
                default => null,
            };
            if (! $provider || $business->wa_provider !== $envelope['provider'] || ! $business->wa_token) {
                return ['outcome' => 'unknown', 'code' => 'provider_tidak_sesuai'];
            }

            $payload = $envelope['payload'];
            if ($envelope['provider'] === 'wablas') {
                $payload['provider_options']['secret_key'] = $business->wa_config['secret_key'] ?? null;
            }

            return $provider->send($envelope['recipient'], $payload, $business->wa_token, $envelope['type']);
        }
        $senderAddress = $business->email_sender_address ?: config('mail.from.address');
        $senderName = $business->email_sender_name ?: config('mail.from.name');
        $payload = $envelope['payload'];
        if ($envelope['type'] === 'verifikasi_email') {
            $url = URL::temporarySignedRoute('receipt.email.confirm', now()->addHours(24),
                ['kodeResi' => $payload['code'], 'version' => $envelope['verification_version']]);
            $subject = 'Konfirmasi email transaksi '.$payload['code'];
            $body = "Konfirmasi alamat email untuk transaksi {$payload['code']} dalam 24 jam:\n{$url}\n\nAbaikan bila Anda tidak meminta perubahan ini.";
        } else {
            $subject = ($envelope['type'] === 'pengingat' ? 'Pengingat pengambilan ' : 'Cucian siap diambil ').$payload['code'];
            $body = view($envelope['type'] === 'pengingat' ? 'emails.reminder' : 'emails.ready', ['data' => $payload])->render();
        }
        try {
            $mailer = $business->smtp_config ? Mail::build([
                'transport' => 'smtp', 'host' => $business->smtp_config['host'], 'port' => $business->smtp_config['port'],
                'scheme' => $business->smtp_config['encryption'] === 'ssl' ? 'smtps' : 'smtp', 'username' => $business->smtp_config['username'],
                'password' => $business->smtp_config['password'], 'timeout' => 30,
            ]) : Mail::mailer();
            $mailer->raw($body, fn ($message) => $message->to($envelope['recipient'])->from($senderAddress, $senderName)->subject($subject));

            return ['outcome' => 'accepted', 'code' => null];
        } catch (TransportExceptionInterface $exception) {
            if ($exception->getCode() >= 500 && $exception->getCode() <= 599) {
                return ['outcome' => 'definitely_rejected', 'code' => 'smtp_menolak'];
            }

            return ['outcome' => 'unknown', 'code' => 'smtp_tidak_pasti'];
        } catch (Throwable) {
            // A disconnect after SMTP DATA may already have delivered the message.
            return ['outcome' => 'unknown', 'code' => 'smtp_tidak_pasti'];
        }
    }
}

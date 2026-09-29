<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NotificationSettingsService
{
    public function technical(User $actor, int $businessId, array $input): void
    {
        app(BusinessTransaction::class)->run($actor, $businessId, function (Business $business) use ($input) {
            $data = Validator::make($input, [
                'email_sender_name' => ['nullable', 'string', 'max:100'],
                'email_sender_address' => ['nullable', 'email', 'max:150'],
                'smtp_config' => ['nullable', 'array:host,port,username,password,encryption'],
                'smtp_config.host' => ['required_with:smtp_config', 'string', 'max:255'],
                'smtp_config.port' => ['required_with:smtp_config', 'integer', 'between:1,65535'],
                'smtp_config.username' => ['required_with:smtp_config', 'string', 'max:255'],
                'smtp_config.password' => ['required_with:smtp_config', 'string', 'max:255'],
                'smtp_config.encryption' => ['required_with:smtp_config', 'in:tls,ssl'],
                'wa_enabled' => ['required', 'boolean'],
                'wa_provider' => ['nullable', 'in:fonnte,wablas,waba'],
                'wa_token' => ['nullable', 'string', 'max:4096'],
                'wa_sender_number' => ['nullable', 'regex:/^62[1-9][0-9]{7,12}$/D'],
                'wa_config' => ['nullable', 'array:base_url,secret_key,phone_number_id,api_version,ready_template_name,reminder_template_name,language_code'],
                'wa_config.base_url' => ['nullable', 'url', 'starts_with:https://'],
                'wa_config.secret_key' => ['nullable', 'string', 'max:4096'],
                'wa_config.phone_number_id' => ['nullable', 'regex:/^[0-9]+$/D', 'max:100'],
                'wa_config.api_version' => ['nullable', 'regex:/^v[0-9]+\.[0-9]+$/D'],
                'wa_config.ready_template_name' => ['nullable', 'regex:/^[a-z0-9_]+$/D', 'max:100'],
                'wa_config.reminder_template_name' => ['nullable', 'regex:/^[a-z0-9_]+$/D', 'max:100'],
                'wa_config.language_code' => ['nullable', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/D', 'max:20'],
            ])->validate();
            if (filled($data['email_sender_name'] ?? null) !== filled($data['email_sender_address'] ?? null)) {
                throw ValidationException::withMessages(['email_sender_address' => 'Nama dan alamat pengirim harus diisi bersama.']);
            }
            $provider = $data['wa_provider'] ?? $business->wa_provider;
            $token = $data['wa_token'] ?? $business->wa_token;
            $config = array_filter([...($business->wa_config ?? []), ...($data['wa_config'] ?? [])], fn ($value) => $value !== null && $value !== '');
            $sender = $data['wa_sender_number'] ?? $business->wa_sender_number;
            if ($provider === 'wablas' && ! empty($config['base_url'])) {
                $host = parse_url($config['base_url'], PHP_URL_HOST);
                if (! is_string($host) || ($host !== 'wablas.com' && ! str_ends_with($host, '.wablas.com')) ||
                    ! in_array(parse_url($config['base_url'], PHP_URL_PATH), [null, '', '/'], true) ||
                    parse_url($config['base_url'], PHP_URL_QUERY) !== null) {
                    throw ValidationException::withMessages(['wa_config.base_url' => 'Gunakan host HTTPS Wablas yang sah.']);
                }
            }
            if ($data['wa_enabled']) {
                if (! $provider || ! $token || ! $sender || ($provider === 'wablas' && empty($config['base_url'])) ||
                    ($provider === 'waba' && (empty($config['phone_number_id']) || empty($config['api_version']) || empty($config['ready_template_name']) || empty($config['reminder_template_name']) || empty($config['language_code']))) ||
                    ($provider === 'wablas' && empty($config['secret_key']))) {
                    throw ValidationException::withMessages(['wa_enabled' => 'Lengkapi penyedia, token, nomor pengirim, dan konfigurasi WA sebelum mengaktifkan.']);
                }
            }
            $wasEnabled = $business->wa_enabled;
            $business->forceFill([
                'email_sender_name' => $data['email_sender_name'] ?? null,
                'email_sender_address' => $data['email_sender_address'] ?? null,
                'wa_enabled' => $data['wa_enabled'], 'wa_provider' => $provider,
                'wa_sender_number' => $sender,
            ]);
            foreach (['smtp_config', 'wa_config', 'wa_token'] as $secret) {
                if (array_key_exists($secret, $data)) {
                    $business->setAttribute($secret, $secret === 'wa_config' ? $config : $data[$secret]);
                }
            }
            $business->save();
            if ($wasEnabled && ! $business->wa_enabled) {
                app(PendingNotificationInvalidator::class)->invalidate('wa_nonaktif', 'whatsapp');
            }
        }, 'administration');
    }

    public function behavior(User $actor, array $input): void
    {
        $data = Validator::make($input, [
            'reminder_enabled' => ['required', 'boolean'],
            'reminder_first_days' => ['required', 'integer', 'between:1,255'],
            'reminder_interval_days' => ['required', 'integer', 'between:1,255'],
            'reminder_max_count' => ['required', 'integer', 'between:1,255'],
            'wa_on_ready' => ['required', 'boolean'],
            'wa_on_reminder' => ['required', 'boolean'],
            'wa_monthly_limit' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
        ])->validate();
        app(BusinessTransaction::class)->run($actor, $actor->business_id, function (Business $business, User $fresh) use ($data) {
            abort_unless($fresh->role === 'owner', 403);
            $old = DB::table('business_settings')->where('business_id', $business->id)->first();
            $occupied = app(WaQuotaService::class)->occupied($business->id);
            if (isset($data['wa_monthly_limit']) && $data['wa_monthly_limit'] < $occupied) {
                throw ValidationException::withMessages(['wa_monthly_limit' => 'Batas lebih kecil dari slot yang sudah terpakai bulan ini.']);
            }
            DB::table('business_settings')->where('business_id', $business->id)->update([...$data, 'updated_at' => now()]);
            $invalidator = app(PendingNotificationInvalidator::class);
            if ($old->reminder_enabled && ! $data['reminder_enabled']) {
                $invalidator->invalidate('pengingat_nonaktif', null, 'pengingat');
            }
            if ($old->wa_on_ready && ! $data['wa_on_ready']) {
                $invalidator->invalidate('wa_siap_nonaktif', 'whatsapp', 'siap_diambil');
            }
            if ($old->wa_on_reminder && ! $data['wa_on_reminder']) {
                $invalidator->invalidate('wa_pengingat_nonaktif', 'whatsapp', 'pengingat');
            }
            if ($data['reminder_max_count'] < $old->reminder_max_count) {
                $invalidator->aboveReminderCount($data['reminder_max_count']);
            }
        });
    }
}

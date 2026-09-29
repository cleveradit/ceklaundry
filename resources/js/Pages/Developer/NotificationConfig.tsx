import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Field, Errors } from '@/Components/Field';

type Business = { id: number; nama: string; email_sender_name: string | null; email_sender_address: string | null; smtp_configured: boolean; wa_enabled: boolean; wa_provider: string | null; wa_sender_number: string | null; wa_token_configured: boolean; wa_config_configured: boolean };

export default function NotificationConfig({ business }: { business: Business }) {
  const form = useForm({ email_sender_name: business.email_sender_name ?? '', email_sender_address: business.email_sender_address ?? '', wa_enabled: business.wa_enabled,
    wa_provider: business.wa_provider ?? '', wa_sender_number: business.wa_sender_number ?? '', wa_token: '',
    smtp_host: '', smtp_port: '587', smtp_username: '', smtp_password: '', smtp_encryption: 'tls',
    wablas_base_url: '', wablas_secret_key: '', waba_phone_number_id: '', waba_api_version: 'v22.0', waba_ready_template: '', waba_reminder_template: '', waba_language_code: 'id' });
  function submit(event: FormEvent) {
    event.preventDefault();
    form.transform(data => ({ email_sender_name: data.email_sender_name || null, email_sender_address: data.email_sender_address || null,
      wa_enabled: data.wa_enabled, wa_provider: data.wa_provider || null, wa_sender_number: data.wa_sender_number || null,
      ...(data.wa_token ? { wa_token: data.wa_token } : {}),
      ...(data.smtp_host || data.smtp_username || data.smtp_password ? { smtp_config: { host: data.smtp_host, port: Number(data.smtp_port), username: data.smtp_username, password: data.smtp_password, encryption: data.smtp_encryption } } : {}),
      ...(data.wablas_base_url || data.wablas_secret_key || data.waba_phone_number_id || data.waba_ready_template || data.waba_reminder_template ? { wa_config: { base_url: data.wablas_base_url || undefined, secret_key: data.wablas_secret_key || undefined, phone_number_id: data.waba_phone_number_id || undefined, api_version: data.waba_api_version, ready_template_name: data.waba_ready_template || undefined, reminder_template_name: data.waba_reminder_template || undefined, language_code: data.waba_language_code } } : {}),
    }));
    form.put(`/dev/businesses/${business.id}/notifications`, { onSuccess: () => form.reset('wa_token', 'smtp_password') });
  }
  return <AppLayout title={`Notifikasi ${business.nama}`} subtitle="Konfigurasi teknis dan rahasia bisnis ini."><section className="panel"><form onSubmit={submit} className="form-stack"><Errors errors={form.errors} />
    <h2>Email</h2><p>SMTP {business.smtp_configured ? 'sudah terkonfigurasi' : 'menggunakan konfigurasi global'}. Kosongkan kolom SMTP untuk mempertahankan pengaturan tersimpan.</p>
    <Field label="Nama pengirim" value={form.data.email_sender_name} onChange={e => form.setData('email_sender_name', e.target.value)} />
    <Field label="Alamat pengirim" type="email" value={form.data.email_sender_address} onChange={e => form.setData('email_sender_address', e.target.value)} />
    <Field label="Host SMTP baru" value={form.data.smtp_host} onChange={e => form.setData('smtp_host', e.target.value)} />
    <Field label="Port SMTP" type="number" value={form.data.smtp_port} onChange={e => form.setData('smtp_port', e.target.value)} />
    <Field label="Pengguna SMTP" value={form.data.smtp_username} onChange={e => form.setData('smtp_username', e.target.value)} />
    <Field label="Password SMTP baru" type="password" value={form.data.smtp_password} onChange={e => form.setData('smtp_password', e.target.value)} />
    <label className="field"><span>Enkripsi SMTP</span><select value={form.data.smtp_encryption} onChange={e => form.setData('smtp_encryption', e.target.value)}><option value="tls">TLS</option><option value="ssl">SSL</option></select></label>
    <h2>WhatsApp otomatis</h2><p>Token {business.wa_token_configured ? 'sudah terkonfigurasi' : 'belum terkonfigurasi'}; opsi penyedia {business.wa_config_configured ? 'tersimpan' : 'belum tersimpan'}.</p>
    <label className="checkbox-field"><input type="checkbox" checked={form.data.wa_enabled} onChange={e => form.setData('wa_enabled', e.target.checked)} /><span>Aktifkan WA otomatis</span></label>
    <label className="field"><span>Penyedia</span><select value={form.data.wa_provider} onChange={e => form.setData('wa_provider', e.target.value)}><option value="">Pilih</option><option value="fonnte">Fonnte</option><option value="wablas">Wablas</option><option value="waba">WhatsApp Business Platform</option></select></label>
    <Field label="Nomor pengirim (62…)" value={form.data.wa_sender_number} onChange={e => form.setData('wa_sender_number', e.target.value)} />
    <Field label="Token baru" type="password" value={form.data.wa_token} onChange={e => form.setData('wa_token', e.target.value)} />
    {form.data.wa_provider === 'wablas' && <><Field label="URL HTTPS Wablas" value={form.data.wablas_base_url} onChange={e => form.setData('wablas_base_url', e.target.value)} /><Field label="Secret key Wablas baru" type="password" value={form.data.wablas_secret_key} onChange={e => form.setData('wablas_secret_key', e.target.value)} /></>}
    {form.data.wa_provider === 'waba' && <><Field label="Phone number ID" value={form.data.waba_phone_number_id} onChange={e => form.setData('waba_phone_number_id', e.target.value)} /><Field label="Versi API" value={form.data.waba_api_version} onChange={e => form.setData('waba_api_version', e.target.value)} /><Field label="Template siap diambil" value={form.data.waba_ready_template} onChange={e => form.setData('waba_ready_template', e.target.value)} /><Field label="Template pengingat" value={form.data.waba_reminder_template} onChange={e => form.setData('waba_reminder_template', e.target.value)} /><Field label="Bahasa template" value={form.data.waba_language_code} onChange={e => form.setData('waba_language_code', e.target.value)} /></>}
    <Button type="submit" disabled={form.processing}>Simpan konfigurasi</Button>
  </form></section></AppLayout>;
}

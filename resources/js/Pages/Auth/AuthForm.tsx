import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Droplets, ArrowRight, ShieldCheck } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Field, Errors } from '@/Components/Field';
import type { Shared } from '@/types';

type Mode = 'login' | 'forgot' | 'reset' | 'change';
const text: Record<Mode, { title: string; subtitle: string; action: string; endpoint: string }> = {
  login: { title: 'Selamat datang kembali.', subtitle: 'Masuk untuk melanjutkan pengelolaan laundry Anda.', action: 'Masuk ke panel', endpoint: '/login' },
  forgot: { title: 'Lupa password?', subtitle: 'Masukkan email akun Anda. Kami akan mengirim petunjuk jika akun dapat menerima email.', action: 'Kirim tautan reset', endpoint: '/forgot-password' },
  reset: { title: 'Buat password baru.', subtitle: 'Tautan hanya dapat digunakan satu kali, dalam 60 menit.', action: 'Simpan password baru', endpoint: '/reset-password' },
  change: { title: 'Amankan akun Anda.', subtitle: 'Ganti password awal sebelum melanjutkan. Password minimal 12 karakter dan maksimal 72 byte.', action: 'Ganti password', endpoint: '/password/change' },
};
export default function AuthForm({ mode, email = '', token = '' }: { mode: Mode; email?: string; token?: string }) {
  const info = text[mode];
  const { flash } = usePage<Shared>().props;
  const form = useForm({ email, token, password: '', password_confirmation: '', current_password: '' });
  return <div className="auth-shell"><Head title={info.title} /><aside className="auth-story"><Link href="/" className="brand"><Droplets size={32} /><span>CekLaundry<small>LAUNDRY LEBIH TERATUR</small></span></Link><div className="auth-story-content"><span className="pill pill-light">TEMAN USAHA LAUNDRY ANDA</span><h1>Lebih rapi.<br />Lebih tenang.<br />Setiap hari.</h1><p>Satukan cabang, tim, dan layanan dalam satu ruang kerja yang mudah digunakan.</p><div className="auth-decoration"><div /><div /><div /></div></div><p className="auth-footnote">Dibuat untuk keseharian usaha laundry.</p></aside><main className="auth-main"><div className="auth-card"><span className="auth-mobile-brand">CekLaundry</span><p className="eyebrow">RUANG KERJA ANDA</p><h2>{info.title}</h2><p className="muted">{info.subtitle}</p>{flash?.success && <div className="notice notice-success" role="status">{flash.success}</div>}<form onSubmit={e => { e.preventDefault(); form.post(info.endpoint, { onFinish: () => form.reset('password', 'password_confirmation', 'current_password') }); }}><Errors errors={form.errors} />
      {mode !== 'change' && <Field label="Email" name="email" type="email" autoComplete="username" maxLength={150} required value={form.data.email} onChange={e => form.setData('email', e.target.value)} />}
      {mode === 'change' && <Field label="Password saat ini" type="password" autoComplete="current-password" required value={form.data.current_password} onChange={e => form.setData('current_password', e.target.value)} />}
      {mode !== 'forgot' && <Field label={mode === 'login' ? 'Password' : 'Password baru'} name="password" type="password" autoComplete={mode === 'login' ? 'current-password' : 'new-password'} minLength={mode === 'login' ? undefined : 12} required value={form.data.password} onChange={e => form.setData('password', e.target.value)} />}
      {(mode === 'change' || mode === 'reset') && <Field label="Ulangi password baru" type="password" autoComplete="new-password" required value={form.data.password_confirmation} onChange={e => form.setData('password_confirmation', e.target.value)} />}
      {mode === 'login' && <div className="forgot-link"><Link href="/forgot-password">Lupa password?</Link></div>}
      <Button className="full-width" type="submit" disabled={form.processing}>{form.processing ? 'Memproses…' : info.action}<ArrowRight size={18} /></Button>
    </form><div className="auth-security"><ShieldCheck size={18} />Akses aman untuk bisnis Anda</div>{mode === 'change' ? <Link href="/logout" method="post" as="button" className="text-link">Keluar dari akun</Link> : mode !== 'login' ? <Link href="/login" className="text-link">Kembali ke halaman masuk</Link> : <p className="form-hint">Belum memiliki akun? Hubungi pengelola aplikasi.</p>}</div></main></div>;
}

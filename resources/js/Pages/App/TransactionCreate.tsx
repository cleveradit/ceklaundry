import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { rupiah } from '@/lib/utils';
import type { Shared } from '@/types';

type Service = { id: number; nama: string; satuan: 'kg' | 'item'; harga: number; berat_minimum: string | null; durasi_jam: number };
type Customer = { id: number; nama: string; no_hp: string };
type Item = { service_id: number; berat_kg: string; jumlah_unit: string; perkiraan_jumlah_baju: string };
type Quote = { subtotal: number; total_akhir: number; fingerprint: string; dp_enabled: boolean; estimasi_selesai: string; items: { nama_layanan_snapshot: string; subtotal: number }[] };
const blankItem = (serviceId: number): Item => ({ service_id: serviceId, berat_kg: '', jumlah_unit: '', perkiraan_jumlah_baju: '' });

export default function TransactionCreate({ branches, branchId, services, customers }: { branches: { id: number; nama: string }[]; branchId: number; services: Service[]; customers: Customer[] }) {
  const writable = usePage<Shared>().props.business?.writable ?? true;
  const [items, setItems] = useState<Item[]>([blankItem(services[0]?.id ?? 0)]);
  const [customerId, setCustomerId] = useState('');
  const [customerQuery, setCustomerQuery] = useState('');
  const [customerOptions, setCustomerOptions] = useState(customers);
  const [customer, setCustomer] = useState({ nama: '', no_hp: '', email: '' });
  const [condition, setCondition] = useState('');
  const [manualEstimate, setManualEstimate] = useState('');
  const [payment, setPayment] = useState({ jumlah: '', metode: 'tunai' });
  const [quote, setQuote] = useState<Quote | null>(null);
  const [key, setKey] = useState(() => crypto.randomUUID());
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [busy, setBusy] = useState(false);
  useEffect(() => {
    if (!customerQuery.trim()) { setCustomerOptions(customers); return; }
    const controller = new AbortController();
    const timer = window.setTimeout(async () => {
      try {
        const response = await fetch(`/app/customers/lookup?${new URLSearchParams({ q: customerQuery })}`, { credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: controller.signal });
        if (response.ok) setCustomerOptions((await response.json()).customers as Customer[]);
      } catch { /* A new keystroke cancels the previous lookup. */ }
    }, 250);
    return () => { window.clearTimeout(timer); controller.abort(); };
  }, [customerQuery, customers]);
  function editItem(index: number, patch: Partial<Item>) { setItems(old => old.map((row, i) => i === index ? { ...row, ...patch } : row)); setQuote(null); }
  function payloadItems() { return items.map(row => { const service = services.find(s => s.id === row.service_id); return { service_id: row.service_id, ...(service?.satuan === 'kg' ? { berat_kg: row.berat_kg } : { jumlah_unit: row.jumlah_unit }), perkiraan_jumlah_baju: row.perkiraan_jumlah_baju || null }; }); }
  async function preview(event: FormEvent) {
    event.preventDefault(); setBusy(true); setErrors({});
    try {
      const response = await fetch('/app/quote', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' }, body: JSON.stringify({ branch_id: branchId, items: payloadItems(), estimasi_selesai: manualEstimate || null }) });
      const result = await response.json();
      if (!response.ok) { setErrors(result.errors ?? { quote: result.message ?? 'Penawaran gagal.' }); setQuote(null); }
      else setQuote(result as Quote);
    } catch { setErrors({ quote: 'Koneksi gagal. Coba lagi.' }); }
    finally { setBusy(false); }
  }
  async function save() {
    if (!quote || !writable) return;
    setBusy(true); setErrors({});
    try {
      const response = await fetch('/app/transactions', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' }, body: JSON.stringify({ branch_id: branchId, customer_id: customerId || null, ...(customerId ? {} : { customer }), items: payloadItems(), request_key: key, quote_fingerprint: quote.fingerprint, catatan_kondisi: condition || null, estimasi_selesai: manualEstimate || null, ...(payment.jumlah ? { initial_payment: { jumlah: Number(payment.jumlah), metode: payment.metode } } : {}) }) });
      const result = await response.json();
      if (!response.ok) {
        if (response.status === 409 && result.quote) setQuote(result.quote as Quote);
        setErrors(result.errors ?? { transaksi: result.message ?? 'Transaksi belum tersimpan.' });
        return;
      }
      setKey(crypto.randomUUID());
      window.location.assign(result.url);
    } catch { setErrors({ transaksi: 'Koneksi gagal. Coba simpan lagi dengan kunci yang sama.' }); }
    finally { setBusy(false); }
  }
  return <AppLayout title="Transaksi baru" subtitle="Harga dihitung server. Express dan reguler yang diambil berbeda waktu dibuat sebagai dua resi." action={<Link className="button button-outline" href="/app">Kembali</Link>}>
    {branches.length > 0 && <label className="field"><span>Cabang</span><select value={branchId} onChange={e => router.get('/app/transactions/create', { branch_id: e.target.value })}>{branches.map(branch => <option key={branch.id} value={branch.id}>{branch.nama}</option>)}</select></label>}
    {Object.keys(errors).length > 0 && <div className="notice notice-danger" role="alert">{Object.values(errors).map((message, index) => <p key={index}>{message}</p>)}</div>}
    <form onSubmit={preview}>
      <section className="panel ops-form"><h2>Pelanggan</h2><label className="field"><span>Cari pelanggan lama</span><input type="search" value={customerQuery} onChange={e => { setCustomerQuery(e.target.value); setCustomerId(''); }} placeholder="Ketik nama atau nomor HP" /></label><label className="field"><span>Pelanggan terdaftar</span><select value={customerId} onChange={e => setCustomerId(e.target.value)}><option value="">Pelanggan baru</option>{customerOptions.map(row => <option key={row.id} value={row.id}>{row.nama} · {row.no_hp}</option>)}</select></label>{!customerId && <div className="ops-grid"><label className="field"><span>Nama</span><input required value={customer.nama} onChange={e => setCustomer({ ...customer, nama: e.target.value })} /></label><label className="field"><span>Nomor HP</span><input required value={customer.no_hp} onChange={e => setCustomer({ ...customer, no_hp: e.target.value })} placeholder="0812…" /></label><label className="field"><span>Email (opsional)</span><input type="email" value={customer.email} onChange={e => setCustomer({ ...customer, email: e.target.value })} /></label></div>}</section>
      <section className="panel ops-form"><h2>Layanan</h2>{services.length === 0 && <p>Belum ada layanan aktif di cabang ini.</p>}{items.map((row, index) => { const service = services.find(s => s.id === row.service_id); return <div className="ops-item" key={index}><label className="field"><span>Layanan {index + 1}</span><select value={row.service_id} onChange={e => editItem(index, { service_id: Number(e.target.value), berat_kg: '', jumlah_unit: '' })}>{services.map(s => <option value={s.id} key={s.id}>{s.nama} · {rupiah(s.harga)}/{s.satuan}</option>)}</select></label>{service?.satuan === 'kg' ? <label className="field"><span>Berat aktual (kg)</span><input type="number" step="0.1" min="0.1" max="9999.9" required value={row.berat_kg} onChange={e => editItem(index, { berat_kg: e.target.value })} /></label> : <label className="field"><span>Jumlah item</span><input type="number" min="1" max="65535" required value={row.jumlah_unit} onChange={e => editItem(index, { jumlah_unit: e.target.value })} /></label>}<label className="field"><span>Perkiraan jumlah baju (opsional)</span><input type="number" min="0" max="65535" value={row.perkiraan_jumlah_baju} onChange={e => editItem(index, { perkiraan_jumlah_baju: e.target.value })} /></label><button className="button button-outline" type="button" disabled={items.length === 1} onClick={() => { setItems(old => old.filter((_, i) => i !== index)); setQuote(null); }}>Hapus baris</button></div>; })}<button className="button button-outline" type="button" disabled={items.length >= 100 || services.length === 0} onClick={() => { setItems(old => [...old, blankItem(services[0].id)]); setQuote(null); }}>Tambah layanan</button></section>
      <section className="panel ops-form"><h2>Kondisi & estimasi</h2><label className="field"><span>Catatan kondisi (terlihat publik)</span><textarea rows={3} maxLength={5000} value={condition} onChange={e => setCondition(e.target.value)} placeholder="Contoh: noda di kerah. Jangan masukkan kontak atau rahasia." /></label><label className="field"><span>Estimasi manual (opsional)</span><input type="datetime-local" value={manualEstimate} onChange={e => { setManualEstimate(e.target.value); setQuote(null); }} /></label></section>
      <div className="ops-actions"><button className="button button-outline" type="submit" disabled={busy || !services.length}>Hitung & periksa harga</button></div>
    </form>
    {quote && <section className="panel ops-form"><h2>Konfirmasi penawaran</h2>{quote.items.map((row, index) => <div className="ops-row" key={index}><span>{row.nama_layanan_snapshot}</span><strong>{rupiah(row.subtotal)}</strong></div>)}<div className="ops-row"><strong>Total akhir</strong><strong>{rupiah(quote.total_akhir)}</strong></div><p>Estimasi: {quote.estimasi_selesai} WIB. Ubah layanan lalu hitung ulang bila perlu.</p><label className="field"><span>Pembayaran saat masuk (opsional)</span><input type="number" min="1" max={quote.total_akhir} value={payment.jumlah} onChange={e => setPayment({ ...payment, jumlah: e.target.value })} /></label>{payment.jumlah && <label className="field"><span>Metode</span><select value={payment.metode} onChange={e => setPayment({ ...payment, metode: e.target.value })}><option value="tunai">Tunai</option><option value="transfer">Transfer</option></select></label>}{!quote.dp_enabled && <p>DP baru sedang nonaktif; pembayaran awal harus penuh atau kosong.</p>}<button className="button button-primary" type="button" disabled={busy || !writable} onClick={save}>Simpan transaksi</button></section>}
  </AppLayout>;
}

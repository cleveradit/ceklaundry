import { Link } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import { rupiah } from '@/lib/utils';

type Service = { id: number; nama: string; satuan: 'kg' | 'item'; harga: number };
type Item = { service_id: number | null; berat_kg: string | null; jumlah_unit: number | null; perkiraan_jumlah_baju: number | null };
type Draft = { service_id: number; berat_kg: string; jumlah_unit: string; perkiraan_jumlah_baju: string };
type Promo = { id: number; nama: string; tipe: string; nilai: number; minimal_total: number | null };
type Loyalty = { is_active: number | boolean; stempel_dibutuhkan: number; berat_maks_gratis: string | null; hadiah: string | null } | null;
type Quote = { fingerprint: string; subtotal: number; potongan_stempel: number; potongan_promo: number; total_akhir: number; reward_item_index: number | null; promo_id: number | null; items: { nama_layanan_snapshot: string; subtotal: number }[] };
const sameRewardName = (serviceName: string, masterName: string | null) => Boolean(masterName) && serviceName.trim().replace(/\s+/gu, ' ').toLocaleLowerCase('id-ID') === masterName?.trim().replace(/\s+/gu, ' ').toLocaleLowerCase('id-ID');

export default function TransactionEdit({ transaction, services, items, promos, loyalty, stampBalance }: { transaction: { id: number; kode_resi: string; branch_id: number; customer_id: number; promo_id: number | null; version: number }; services: Service[]; items: Item[]; promos: Promo[]; loyalty: Loyalty; stampBalance: number }) {
  const [rows, setRows] = useState<Draft[]>(items.map(row => ({ service_id: row.service_id ?? services[0]?.id ?? 0, berat_kg: row.berat_kg ?? '', jumlah_unit: String(row.jumlah_unit ?? ''), perkiraan_jumlah_baju: String(row.perkiraan_jumlah_baju ?? '') })));
  const [rewardItemIndex, setRewardItemIndex] = useState('');
  const [promoId, setPromoId] = useState(String(transaction.promo_id ?? ''));
  const [quote, setQuote] = useState<Quote | null>(null);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [busy, setBusy] = useState(false);
  const payload = () => rows.map(row => ({ service_id: row.service_id, ...(services.find(service => service.id === row.service_id)?.satuan === 'kg' ? { berat_kg: row.berat_kg } : { jumlah_unit: row.jumlah_unit }), perkiraan_jumlah_baju: row.perkiraan_jumlah_baju || null }));
  const selection = () => ({ reward_item_index: rewardItemIndex === '' ? null : Number(rewardItemIndex), promo_id: promoId || null });
  const change = (index: number, patch: Partial<Draft>) => { setRows(old => old.map((row, i) => i === index ? { ...row, ...patch } : row)); if ('service_id' in patch) setRewardItemIndex(''); setQuote(null); };
  const headers = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' };
  async function preview(event: FormEvent) {
    event.preventDefault(); setBusy(true);
    try {
      const response = await fetch('/app/quote', { method: 'POST', credentials: 'same-origin', headers, body: JSON.stringify({ branch_id: transaction.branch_id, customer_id: transaction.customer_id, items: payload(), ...selection() }) });
      const data = await response.json();
      if (!response.ok) { setErrors(data.errors ?? { quote: data.message }); setQuote(null); } else { setErrors({}); setQuote(data); }
    } catch { setErrors({ quote: 'Koneksi gagal. Coba lagi.' }); } finally { setBusy(false); }
  }
  async function save() {
    if (!quote) return;
    setBusy(true);
    try {
      const response = await fetch(`/app/transactions/${transaction.id}`, { method: 'PUT', credentials: 'same-origin', headers, body: JSON.stringify({ expected_version: transaction.version, items: payload(), quote_fingerprint: quote.fingerprint, ...selection() }) });
      const data = await response.json();
      if (!response.ok) { setErrors(data.errors ?? { transaksi: data.message ?? 'Harga belum tersimpan.' }); if (response.status === 409 && data.quote) { setRewardItemIndex(String(data.quote.reward_item_index ?? '')); setPromoId(String(data.quote.promo_id ?? '')); setQuote(data.quote); } else setQuote(null); return; }
      window.location.assign(data.url);
    } catch { setErrors({ transaksi: 'Koneksi gagal. Muat ulang sebelum mencoba lagi.' }); } finally { setBusy(false); }
  }
  return <AppLayout title={`Ubah layanan ${transaction.kode_resi}`} subtitle="Harga terbaru akan ditampilkan untuk konfirmasi sebelum disimpan." action={<Link className="button button-outline" href={`/app/transactions/${transaction.id}`}>Kembali</Link>}>
    {Object.values(errors).length > 0 && <div className="notice notice-danger">{Object.values(errors).map((error, i) => <p key={i}>{error}</p>)}</div>}
    <form onSubmit={preview}><section className="panel ops-form">{rows.map((row, index) => <div className="ops-item" key={index}><label className="field"><span>Layanan</span><select value={row.service_id} onChange={e => change(index, { service_id: Number(e.target.value), berat_kg: '', jumlah_unit: '' })}>{services.map(service => <option key={service.id} value={service.id}>{service.nama} · {rupiah(service.harga)}</option>)}</select></label>{services.find(service => service.id === row.service_id)?.satuan === 'kg' ? <label className="field"><span>Berat (kg)</span><input required type="number" min="0.1" max="9999.9" step="0.1" value={row.berat_kg} onChange={e => change(index, { berat_kg: e.target.value })} /></label> : <label className="field"><span>Jumlah item</span><input required type="number" min="1" max="65535" value={row.jumlah_unit} onChange={e => change(index, { jumlah_unit: e.target.value })} /></label>}<label className="field"><span>Perkiraan jumlah baju</span><input type="number" min="0" max="65535" value={row.perkiraan_jumlah_baju} onChange={e => change(index, { perkiraan_jumlah_baju: e.target.value })} /></label><button className="button button-outline" type="button" disabled={rows.length === 1} onClick={() => { setRows(old => old.filter((_, i) => i !== index)); setRewardItemIndex(''); setQuote(null); }}>Hapus</button></div>)}<button className="button button-outline" type="button" disabled={rows.length >= 100} onClick={() => { setRows(old => [...old, { service_id: services[0].id, berat_kg: '', jumlah_unit: '', perkiraan_jumlah_baju: '' }]); setQuote(null); }}>Tambah layanan</button></section>
      <section className="panel ops-form"><h2>Stempel dan promo</h2>{loyalty?.is_active && <><p>Saldo {stampBalance} stempel. Hadiah {loyalty.hadiah} memerlukan {loyalty.stempel_dibutuhkan} stempel, maksimal {loyalty.berat_maks_gratis} kg.</p>{stampBalance >= loyalty.stempel_dibutuhkan && <label className="field"><span>Baris hadiah</span><select value={rewardItemIndex} onChange={e => { setRewardItemIndex(e.target.value); setQuote(null); }}><option value="">Tanpa hadiah</option>{rows.map((row, index) => { const service = services.find(candidate => candidate.id === row.service_id); return service?.satuan === 'kg' && sameRewardName(service.nama, loyalty.hadiah) ? <option key={index} value={index}>Baris {index + 1} · {service.nama}</option> : null; })}</select></label>}{stampBalance < loyalty.stempel_dibutuhkan && <p>Stempel belum cukup untuk menawarkan hadiah.</p>}</>}{!loyalty?.is_active && <p>Program stempel sedang nonaktif.</p>}<label className="field"><span>Promo</span><select value={promoId} onChange={e => { setPromoId(e.target.value); setQuote(null); }}><option value="">Tanpa promo</option>{promos.map(promo => <option value={promo.id} key={promo.id}>{promo.nama} · {promo.tipe === 'persen' ? `${promo.nilai}%` : rupiah(promo.nilai)}{promo.minimal_total ? ` · min ${rupiah(promo.minimal_total)}` : ''}</option>)}</select></label></section><button className="button button-outline" type="submit" disabled={busy}>Hitung ulang harga</button></form>
    {quote && <section className="panel ops-form"><h2>Konfirmasi harga baru</h2>{quote.items.map((row, i) => <div className="ops-row" key={i}><span>{row.nama_layanan_snapshot}</span><strong>{rupiah(row.subtotal)}</strong></div>)}<div className="ops-row"><span>Subtotal</span><strong>{rupiah(quote.subtotal)}</strong></div>{quote.potongan_stempel > 0 && <div className="ops-row"><span>Stempel</span><strong>−{rupiah(quote.potongan_stempel)}</strong></div>}{quote.potongan_promo > 0 && <div className="ops-row"><span>Promo</span><strong>−{rupiah(quote.potongan_promo)}</strong></div>}<div className="ops-row"><strong>Total baru</strong><strong>{rupiah(quote.total_akhir)}</strong></div><button className="button button-primary" disabled={busy} onClick={save}>Simpan harga baru</button></section>}
  </AppLayout>;
}

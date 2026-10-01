import { useEffect, useRef, useState } from 'react';

export default function PwaUpdateNotice() {
  const [waiting, setWaiting] = useState<ServiceWorker | null>(null);
  const [needsReload, setNeedsReload] = useState(false);
  const dirty = useRef(false);

  useEffect(() => {
    if (!('serviceWorker' in navigator)) return;
    const check = () => { void navigator.serviceWorker.getRegistration().then(reg => setWaiting(reg?.waiting ?? null)); };
    const onInput = (event: Event) => {
      if ((event.target as Element | null)?.matches('input, textarea, select, [contenteditable]')) dirty.current = true;
    };
    const onController = () => {
      if (dirty.current) setNeedsReload(true);
      else window.location.reload();
    };
    check();
    window.addEventListener('pwa-update-ready', check);
    window.addEventListener('pwa-controller-changed', onController);
    document.addEventListener('input', onInput, true);
    document.addEventListener('change', onInput, true);
    return () => {
      window.removeEventListener('pwa-update-ready', check);
      window.removeEventListener('pwa-controller-changed', onController);
      document.removeEventListener('input', onInput, true);
      document.removeEventListener('change', onInput, true);
    };
  }, []);

  useEffect(() => {
    if (waiting && !dirty.current) waiting.postMessage({ type: 'ACTIVATE' });
  }, [waiting]);

  if (!waiting && !needsReload) return null;
  return <aside className="notice" role="status">
    <strong>Pembaruan CekLaundry tersedia</strong>
    <span>{dirty.current ? 'Simpan perubahan pada formulir sebelum memuat ulang.' : 'Versi terbaru siap dipakai.'}</span>
    <button className="button button-secondary" type="button" onClick={() => {
      if (dirty.current && !window.confirm('Perubahan formulir yang belum disimpan akan hilang. Muat ulang sekarang?')) return;
      if (waiting) waiting.postMessage({ type: 'ACTIVATE' });
      else window.location.reload();
    }}>Muat versi terbaru</button>
  </aside>;
}

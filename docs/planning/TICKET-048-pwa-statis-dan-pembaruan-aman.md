# Implementation Plan: TICKET-048 (PWA statis dan pembaruan aman)

**Ticket:** `TICKET-048`

**Status:** `REVIEW`

**Target Audience:** AI Developer Agents

**Depends On:** TICKET-001–043 (M1–M5 selesai); dapat dikerjakan terpisah dari TICKET-044–047

**Tahap:** M6 — US-605, US-606

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M6](../plan.md#10-m6--demo-dan-pwa), [PRD 5.I](../initiate-file/prd.md), [US-605–606](../initiate-file/user-stories.md), [arsitektur 8](../initiate-file/architecture.md) |
| Install | Manifest memuat nama, ikon, warna tema, dan splash/launch metadata; panel owner/admin dapat dipasang di home screen pada target Android/iOS. Keterbatasan iOS didokumentasikan. |
| Cache | Service worker hanya meng-cache aset build dengan nama ber-hash dan halaman offline. Semua HTML/Inertia/status/auth/print/API/CSV dan data dinamis `network-only` + `no-store`; offline tidak menyimpan atau replay write. |
| Update | Cache build lama dibuang saat aktivasi; versi baru diperiksa saat launch dan dipakai pada navigasi aman. Form yang belum tersimpan mendapat pemberitahuan sebelum reload; logout, switch akun/tenant, dan browser back tidak menampilkan data lama. |
| Keterlacakan | FR-W01/W02; US-605 AC 1, US-606 AC 1–4; KOM-03, AND-26, SEC-04, ISO-05, UX-03. |
| Otorisasi | Pengguna meminta penyusunan tiket M6; implementasi belum diminta. |

## 2. Objective

Panel CekLaundry dapat dipasang sebagai PWA ringan dan memuat aset statis lebih cepat pada kunjungan ulang. Ketika jaringan mati, hanya halaman offline yang tampil; data bisnis dan operasi tulis selalu memerlukan server.

## 3. Non-Negotiable Technical Contract

1. `public/manifest.webmanifest`, `public/icons/`, `public/offline.html`, `resources/views/app.blade.php`: pasang manifest dan aset install yang sesuai identitas produk, dengan metadata Android/iOS yang didukung.
2. `public/sw.js`, `resources/js/app.tsx`, `vite.config.ts`: registrasi service worker hanya pada konteks aman; allowlist cache untuk path aset Vite ber-hash dan offline page, cache versioning/cleanup, serta seluruh navigasi/data menggunakan jaringan tanpa cache respons dinamis.
3. `resources/js/Layouts/AppLayout.tsx`, `resources/js/Components/PwaUpdateNotice.tsx`: cek update pada launch, gunakan versi baru pada navigasi aman, dan tampilkan pemberitahuan sebelum reload bila form berubah/belum tersimpan. Jangan memaksa reload yang membuang input diam-diam.
4. `app/Http/Middleware/NoStore.php`, `resources/views/public/status.blade.php`, `routes/web.php`: pertahankan `Cache-Control: no-store` pada HTML, Inertia, auth, status publik, print, dan respons data; route offline/manifest/SW tidak membuka data tenant.
5. `tests/browser/m6.cjs`, `tests/Feature/PwaHeadersTest.php`: uji install metadata, cache allowlist, offline, deploy aset baru, serta tenant A → logout → tenant B → back/offline.

## 4. Scope of Changes

1. Tambahkan manifest, ikon ukuran yang dibutuhkan, warna tema, dan halaman offline berbahasa Indonesia; cek tampilan install di viewport HP.
2. Tulis service worker dengan cache name versi build, allowlist aset hashed dan offline page saja. Jangan cache URL dengan query/response dinamis; jangan antrekan POST atau menyediakan fungsi offline bisnis.
3. Pasang mekanisme update yang mengenali halaman dengan input berubah. Tunda reload sampai pengguna menyimpan/mengonfirmasi, lalu gunakan versi baru; bersihkan cache build lama saat aktivasi.
4. Uji logout, role switch, pergantian akun/tenant, pageshow/bfcache, dan browser back terhadap data tenant sebelumnya. Server tetap menjadi sumber data saat kembali online.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Install | Owner/admin membuka panel di HP target | Nama, ikon, warna, dan splash/launch sesuai manifest | `[ ]` |
| Kunjungan ulang | Buka panel dengan jaringan tersedia | Aset hashed dapat berasal dari cache; data diambil ulang dari server | `[ ]` |
| Offline | Buka aplikasi tanpa jaringan | Halaman `Anda sedang offline`; tidak ada data tenant atau fungsi tulis offline | `[ ]` |
| Batas cache | Periksa Cache Storage sesudah status/auth/panel/CSV/print | Hanya aset hashed dan offline page; respons dinamis tidak ada | `[ ]` |
| Pergantian tenant | Login A → logout → login B → back/offline | Tidak ada identitas/data A yang ditampilkan | `[ ]` |
| Deploy baru | Aset versi baru tersedia saat form kotor/bersih | Form kotor diberi pemberitahuan dan tidak hilang; navigasi aman memakai aset baru | `[ ]` |
| Gagal tulis offline | Submit form saat offline lalu online | Tidak ada request yang di-replay diam-diam | `[ ]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=PwaHeadersTest`
2. `rtk proxy docker build --target frontend -t ceklaundry-frontend -f docker/php/Dockerfile .`
3. `rtk proxy docker run --rm ceklaundry-frontend npm run lint`
4. `rtk proxy docker run --rm ceklaundry-frontend npm run typecheck`
5. `rtk proxy docker run --rm ceklaundry-frontend npm run build`

Expected: cache hanya berisi aset yang diizinkan, data dinamis network-only, dan update aman; bukti browser/perangkat dicatat pada TICKET-049.

## 7. Out of Scope

1. Offline write, background sync, push notification, atau aplikasi native.
2. Meng-cache HTML/API/data tenant demi tampilan offline.

## 8. Completion Checklist

- [ ] Otorisasi implementasi diterima dan status menjadi `READY`.
- [ ] Manifest, ikon, service worker, offline page, dan update aman diterapkan.
- [ ] Pengujian cache, pergantian tenant, dan form kotor lulus.
- [ ] Keterbatasan iOS dan bukti perangkat dicatat jujur.

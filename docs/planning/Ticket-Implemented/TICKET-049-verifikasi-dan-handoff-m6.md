# Implementation Plan: TICKET-049 (Verifikasi terpadu dan handoff M6)

**Ticket:** `TICKET-049`

**Status:** `DONE`

**Target Audience:** AI Developer Agents

**Depends On:** `TICKET-044`–`TICKET-048`

**Tahap:** M6 — kriteria selesai

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M6](../../plan.md#10-m6--demo-dan-pwa), [US-601–606](../../initiate-file/user-stories.md), [audit M5](../../audits/m5-verification.md) |
| Bukti | Matriks seluruh 17 AC US-601–606 dan enam kriteria selesai plan M6; pisahkan uji otomatis, MySQL concurrency, browser desktop/HP, perangkat Android/iOS, serta CI. Catat keterbatasan bila perangkat fisik tidak tersedia. |
| Keamanan | Dua demo terpisah, nol outbound nyata, expiry/purge, dan cache tenant A→B harus terbukti. Regresi M1–M5 yang tersentuh wajib lulus. |
| Handoff | Dokumentasi fitur berstatus Live hanya setelah implementasi dan verifikasi; tahap 7 staging/restore dan tahap 8 produksi tetap pekerjaan berikutnya. |
| Keterlacakan | FR-M01–M06, FR-W01/W02; US-601–606; AND-12/25/26, DAT-02, SEC-02/04, ISO-01/02/05, KOM-03. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-044–049 pada 1 Oktober 2026. |

## 2. Objective

Membuktikan demo dan PWA memenuhi setiap acceptance criterion serta aman saat dipakai bersama fitur M1–M5. Hasil aktual, batas bukti, dan pekerjaan staging berikutnya tercatat sebelum M6 dinyatakan selesai.

## 3. Non-Negotiable Technical Contract

1. `tests/Feature/DemoProvisionTest.php`, `tests/Feature/DemoRoleTest.php`, `tests/Feature/DemoNotificationTest.php`, `tests/Feature/PwaHeadersTest.php`, `tests/Integration/DemoProvisionConcurrencyTest.php`, `tests/Integration/DemoOutboundGuardTest.php`, `tests/Integration/DemoPurgeTest.php`, `tests/Integration/DemoPurgeConcurrencyTest.php`, `tests/browser/m6.cjs`: seluruh AC, batas, kegagalan, tenant/cabang, scheduler, dan cache.
2. `package.json`, `.github/workflows/ci.yml`: tambah browser M6 ke gate CI dengan database QA terpisah; jalankan backend MySQL 8.4, Pint, frontend lint/typecheck/build, validator dokumen, dan browser M1–M6.
3. `docs/audits/m6-verification.md`: catat tiap AC US-601–606 (17 AC) dan enam kriteria selesai plan M6, perintah/hasil aktual, bukti CI/browser/perangkat, serta batas yang belum diverifikasi.
4. `docs/features/m6-demo-dan-pwa.md`, `docs/features/index.md`: dokumentasikan hanya perilaku yang terbukti Live, termasuk batas PWA di iOS.
5. `docs/development.md`, `docs/planning/current-session.md`, `docs/planning/index.md`, `docs/planning/Ticket-Implemented/index.md`: perbarui perintah QA/handoff; arsipkan TICKET-044–049 hanya setelah `DONE`.

## 4. Scope of Changes

1. Uji dua pengunjung demo dari halaman depan hingga owner/admin, transaksi, notifikasi simulasi, status publik, expiry, lalu purge. Cocokkan fixture/ledger/payment dan pastikan tenant/cabang tidak bocor.
2. Paksa interleaving MySQL nyata pada limiter provision dan dua purge; uji scheduler outage, job lama, reset akun, dan semua jalur outbound dengan fake transport yang menghitung panggilan.
3. Uji install/offline/update/cache pada desktop dan HP; ulangi tenant A→logout→tenant B/back/offline serta form belum disimpan. Catat Android/iOS nyata atau batas bukti jika tidak tersedia.
4. Jalankan regresi M1–M5, CI, validator dokumen, dan dokumentasikan staging/restore/produksi sebagai tahap setelah M6 tanpa mengklaimnya selesai.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Demo utuh | Dua prospek provision dan menjalankan alur M1–M5 | Fixture persis, tenant terpisah, admin Cabang Utama saja | `[x]` |
| Komunikasi | Semua kanal otomatis/manual, reset, verifikasi, tautan | Log simulasi eligible; nol email/WA/tautan eksternal | `[x]` |
| Expiry/purge | Batas 7×24 jam, outage, dua purge paralel | Akses langsung ditolak; domain+infra bersih tanpa sentuh tenant nyata | `[x]` |
| Rate limit | Empat provision per IP sehari dan race | Tiga berhasil, keempat 429, tidak ada over-provision | `[x]` |
| PWA aman | Install, offline, deploy baru, form kotor, tenant A→B | Hanya aset statis cache; tidak ada data lama/write replay/input hilang diam-diam | Browser/cache/form lulus; instalasi fisik dan deploy dua versi perangkat belum diuji |
| Handoff | Semua suite, browser, CI, audit, feature docs | Klaim M6 bertaut bukti; staging/produksi tidak diklaim selesai | `[x]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --fail-on-warning`
2. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
3. `rtk proxy docker build --target frontend -t ceklaundry-frontend -f docker/php/Dockerfile .`
4. `rtk proxy docker run --rm ceklaundry-frontend npm run lint`
5. `rtk proxy docker run --rm ceklaundry-frontend npm run typecheck`
6. `rtk proxy docker run --rm ceklaundry-frontend npm run build`
7. `rtk proxy python3 docs/audits/validate-final-specs.py`
8. `rtk git diff --check`

Hasil: seluruh gate lulus pada [CI `96ba067`](https://github.com/cleveradit/ceklaundry/actions/runs/36906446978), termasuk `npm run test:browser:m6`. Batas perangkat aktual dicatat di [audit M6](../../audits/m6-verification.md).

## 7. Out of Scope

1. Deploy staging/produksi, provider nyata, dan restore backup fisik (plan tahap 7–8).
2. Menyebut produk siap operasi nyata hanya berdasarkan M6 atau CI hijau.

## 8. Completion Checklist

- [x] Otorisasi implementasi diterima; status `DONE` setelah verifikasi lokal/CI.
- [x] Seluruh AC/kriteria M6 memiliki bukti dan batas di audit.
- [x] Gate lokal, browser, MySQL concurrency, dan CI remote lulus.
- [x] Feature docs/handoff diperbarui dan tiket `DONE` diarsipkan.

Bukti aktual dan batas pengujian perangkat ada di [audit M6](../../audits/m6-verification.md). Status DONE mencakup implementasi dan gate lokal/CI; pemasangan serta splash Android/iOS fisik tetap belum diverifikasi.

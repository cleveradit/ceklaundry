# Implementation Plan: TICKET-010 (Verifikasi terpadu dan handoff M1)

**Ticket:** `TICKET-010`  
**Status:** `READY`  
**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-002`, `TICKET-003`, `TICKET-004`, `TICKET-005`, `TICKET-006`, `TICKET-007`, `TICKET-008`, `TICKET-009`  
**Tahap:** Urutan 1 — M1, pekerjaan 9 dan kriteria selesai

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M1](../plan.md#5-m1--fondasi-dan-tenant), [US-101–US-109](../initiate-file/user-stories.md#epic-m1--fondasi--tenant), [NFR](../initiate-file/nfr.md), [audit final](../audits/final-system-audit.md) |
| Keterlacakan | Seluruh US-101–US-109 pada cakupan M1; PLH-01–PLH-04, ISO-01–ISO-06, SEC-01–SEC-05, SEC-07, SEC-08, AND-01, AND-08, AND-19, AND-21, AND-27, DAT-01, UX-01, UX-02, UX-04, LOK-01–LOK-04 |
| Bukti | Pisahkan uji service/fixture M1 dengan integrasi fitur M2–M6 yang belum tersedia; tidak memberi status lulus palsu pada AC lintas milestone |
| CI | Gate kualitas, unit/feature, integrasi MySQL8.4 paralel, Vite dan image sejak TICKET-002; lengkapi cakupan M1 di sini |
| Otorisasi | Pengguna menyetujui dan meminta implementasi seluruh TICKET-001–010 pada 28 September 2026. |

## 2. Objective

Membuktikan fondasi M1 dapat dipasang ulang dan dipakai bersama dari bootstrap hingga sinkronisasi layanan. Menghasilkan handoff yang jelas untuk M2 tanpa mengklaim alur operasional, notifikasi, atau kesiapan produksi sudah selesai.

## 3. Non-Negotiable Technical Contract

1. `.github/workflows/ci.yml`: seluruh suite M1, Pint, ESLint, TypeScript strict, Vite, image; MySQL8.4 nyata untuk integrasi, barrier dua proses/koneksi untuk concurrency. Tidak hanya SQLite atau satu enclosing transaction.
2. `tests/Feature/Foundation/M1JourneyTest.php`: bootstrap developer→bisnis/owner→ganti password→cabang/admin→master/lokal→preview/sync; bisnis kedua dan sesi lama untuk uji isolasi.
3. `docs/audits/m1-verification.md`: matriks tiap AC US-101–US-109 ke test/bukti/status aktual, termasuk negatif, batas dan failure; tandai integrasi masa depan sebagai belum diverifikasi, dengan milestone pemilik.
4. `docs/development.md`, `docs/ai-context.md`, `docs/architecture.md`, `docs/data-model.md`: perintah nyata setup bersih, secret injection, migrasi, bootstrap, build, web/worker/cron, lint/tes, cleanup DB uji; bedakan rancangan dan implementasi.
5. `docs/features/index.md`, `docs/features/m1-fondasi-dan-tenant.md`: hanya fitur benar-benar diimplementasikan dan diverifikasi diberi status Live; tidak mencantumkan fitur M2+ sebagai tersedia.
6. `docs/planning/current-session.md`, `docs/planning/index.md`: hasil, keterbatasan, dan langkah pertama M2; arsip ticket DONE menurut workflow, tanpa menandai semua milestone selesai.

## 4. Scope of Changes

### A. Verifikasi terpadu

1. Checkout bersih/DB kosong sekali pakai; ikuti dokumentasi tanpa langkah tersembunyi.
2. Buat dua bisnis/multi-cabang dan uji role, manipulasi ID, payload, session lama, lifecycle serta reset di read-only.
3. Uji fault/rollback/parallel M1 dan seluruh gate CI; perbaiki defect dalam lingkup M1 sebelum selesai.
4. Uji manual desktop/HP untuk keterbacaan, menu owner5–6, teks16px, kontras4,5:1, target44px, feedback Indonesia; dokumentasikan perangkat/browser aktual.

### B. Handoff lintas milestone

| Kontrak | Bukti M1 | Uji lanjutan wajib |
|---|---|---|
| US-103 statistik developer | Query agregat dengan transaksi fixture lengkap | M2 dengan transaksi yang dibuat alur nyata |
| US-104 AC4–6/8 | Evaluator akses dan invalidasi log fixture | M2 GET resi; M3 email publik/otomatis/manual/wa.me dan off→on worker |
| US-105 AC1/3/4/5 | Identitas, histori fixture, lock nonaktif vs writer fixture | M2 resi/status dan create nyata; M5 histori laporan |
| US-107/108 snapshot dan hadiah | Katalog/snapshot fixture/guard loyalty_settings | M2 pricing/quote/snapshot nyata; M4 hadiah aktif lewat UI/domain |
| US-109 AC1–6 | Resource M1, scope/policy/DB, context jobs dan DTO negatif | M2–M5 setiap route customer/transaksi/payment/ledger/log/search/report/export baru |
| US-101 demo/restore | Fixture demo, encrypted auth job, guard hold/cutoff dan cleanup auth | M3 restore seluruh transport; M6 provisioning dan sesi demo nyata |
| AND-19 | Lock/assignment/sync M1 dan writer fixture | M2 create/payment/quote yang memakai service sesungguhnya |

Ini bukan pengurangan AC atau pemindahan milestone fitur M1. Perilaku M1 harus diimplementasikan sekarang; ketika consumer berikutnya tersedia, pengujian integrasinya wajib ditambahkan. M1 tidak boleh dinyatakan lulus E2E untuk endpoint yang belum dibuat.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Setup berulang | Checkout/DB kosong, instruksi dokumentasi | Aplikasi/build/migrasi/bootstrap/web/worker/cron dapat diulang | `[ ]` |
| Perjalanan M1 | Developer→owner→admin→katalog/sync | Alur lengkap berfungsi, role dan wajib ganti password benar | `[ ]` |
| Isolasi | Dua bisnis dan dua cabang, ID/body/query dimanipulasi | 404/403 sesuai kontrak; developer tanpa detail operasional | `[ ]` |
| Batas lifecycle | WIB−7/0/+7/+8, nonaktif dan sesi lama | Banner/akses benar; keamanan akun tidak deadlock | `[ ]` |
| Integritas | Stale sync, fault multi-cabang, perubahan role paralel | Konflik/rollback sesuai kontrak; tanpa partial commit/bocoran | `[ ]` |
| CI gagal | Salah satu gate gagal | Pipeline gagal dan M1 belum ditutup sampai diperbaiki | `[ ]` |
| Bukti belum tersedia | AC menyentuh route transaksi/WA/report/demo | Dicatat belum diverifikasi integrasinya beserta milestone pemilik; tidak ditandai lulus | `[ ]` |

## 6. Verification Commands

Target sesudah seluruh implementasi. DB uji harus terpisah; jangan menghapus volume/data pengembangan pengguna.

```bash
rtk proxy docker compose config --quiet
rtk proxy docker compose up -d --build
rtk proxy docker compose exec -T app php artisan migrate --force
rtk proxy docker compose exec -T app php artisan test
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk proxy docker build --target frontend -t ceklaundry-frontend -f docker/php/Dockerfile .
rtk proxy docker run --rm ceklaundry-frontend npm run lint
rtk proxy docker run --rm ceklaundry-frontend npm run typecheck
rtk proxy docker run --rm ceklaundry-frontend npm run build
rtk proxy python3 docs/audits/validate-final-specs.py
rtk git diff --check
```

Simpan ringkasan hasil test, CI, setup bersih dan browser dalam audit M1. Bukti CI harus berasal dari run aktual; jika belum dapat dijalankan di remote, catat belum terverifikasi dan jangan mengklaim CI lulus.

## 7. Out of Scope

1. Implementasi tahap2–8 atau semua fitur M2–M6 demi melengkapi consumer test.
2. Deploy produksi, backup/restore drill penuh, PWA, printer thermal dan target performa laporan/transaksi yang belum tersedia.
3. Klaim siap produksi hanya dari build/CI/dokumentasi.

## 8. Completion Checklist

- [ ] Lingkup diotorisasi; semua dependensi diimplementasikan dan diverifikasi.
- [ ] Setup bersih serta seluruh gate CI M1 lulus dengan bukti aktual.
- [ ] Matriks AC M1 lengkap dengan batas fixture/integrasi masa depan yang jujur.
- [ ] Perjalanan manual, isolasi, failure dan concurrency M1 selesai tanpa blocker.
- [ ] Dokumentasi/feature docs/status/arsip/sesi diperbarui; langkah M2 jelas.

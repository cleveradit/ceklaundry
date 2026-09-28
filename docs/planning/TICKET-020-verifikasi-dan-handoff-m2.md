# Implementation Plan: TICKET-020 (Verifikasi terpadu dan handoff M2)

**Ticket:** `TICKET-020`
**Status:** `READY`
**Hasil sementara:** [Audit M2](../audits/m2-verification.md) mencatat 75 tes/671 assertions, gate kualitas lokal, browser M1/M2, PDF 58 mm dan QR yang didekode. [CI remote](https://github.com/cleveradit/ceklaundry/actions/runs/36462815134) lulus; printer thermal fisik masih memerlukan bukti sebelum DONE/arsip.
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-011`–`TICKET-019`
**Tahap:** M2 — kriteria selesai

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M2](../plan.md#6-m2--operasional-inti), [US-201–215](../initiate-file/user-stories.md), [audit M1](../audits/m1-verification.md) |
| Keterlacakan | Seluruh AC M2 yang sudah dapat dijalankan; AND-01/02/06/08/09/13–19/22–24, ISO-01–06, SEC-02/04/06/08, UX-03–05 |
| Bukti | Pisahkan uji fixture/integrasi M3/M4/M5/M6 dari alur M2 nyata; semua batas dicatat per AC. |
| Lingkungan | MySQL8.4 QA terpisah; jangan hapus volume development. Backend suite dan browser seed dijalankan berurutan. |
| Otorisasi | Pengguna menyetujui implementasi seluruh TICKET-011–020. |

## 2. Objective

Membuktikan alur pelanggan baru sampai cucian lunas dan diambil berjalan di browser serta aman terhadap retry, race dan isolasi. Handoff menunjukkan fitur yang kini nyata, hasil verifikasi, dan integrasi lanjutan yang wajib diuji pada M3–M6.

## 3. Non-Negotiable Technical Contract

1. `tests/Feature/Operations/M2JourneyTest.php`, `tests/Integration/M2ConcurrencyTest.php`, `tests/browser/m2.cjs`: perjalanan dua bisnis/multi-cabang, retry dan dua koneksi MySQL, browser desktop/HP, no-JS publik, cetak/QR.
2. `docs/audits/m2-verification.md`: pemetaan setiap AC US-201–215 ke test/bukti/status dan batas milestone; uji ulang handoff M1 pada endpoint nyata.
3. `docs/features/m2-operasional-inti.md`, `docs/features/index.md`: status Live hanya setelah verifikasi; jelaskan perilaku dan gotcha aktual.
4. `docs/development.md`, `docs/architecture.md`, `docs/data-model.md`, `docs/planning/index.md`, `docs/planning/current-session.md`: perintah nyata, hasil, keterbatasan, dan urutan M3; arsip TICKET-011–020 hanya setelah DONE.
5. `.github/workflows/ci.yml`: gate backend/MySQL concurrency, Pint, ESLint, TypeScript, Vite dan browser M2; simpan bukti run aktual.

## 4. Scope of Changes

1. Checkout/DB QA kosong, migrasi, transaksi Rp74.500→DP Rp30.000→lunas Rp44.500→ambil; cari, cetak, scan QR, buka status publik.
2. Jalankan kasus batal, edit harga terkunci, owner lintas cabang, admin dibatasi, developer tanpa data operasi, bisnis baca-saja/nonaktif.
3. Fault injection dan race create/payment/status/merge; ulangi statistik developer, snapshot sync dan guard cabang M1 dengan data transaksi nyata.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Perjalanan | Customer baru→Rp74.500→DP30.000→proses→siap→lunas44.500→ambil | Sisa benar; pickup sebelum lunas ditolak; history/receipt/publik konsisten | Lulus lokal |
| Retry/race | Double click create/payment; dua pembayaran, edit vs bayar/batal | Tanpa duplikat/overpay/partial commit | Lulus inti; batas writer lanjutan di audit |
| Isolasi | Dua bisnis, multi-cabang, owner/admin/developer, ID dimanipulasi | 404/403/423 sesuai kontrak, tanpa kebocoran | Lulus lokal |
| Publik | JS mati, kode salah, rate limit, inspect HTML/back | Fungsi inti bekerja, 429 dan masking/header benar | Lulus lokal |
| Cetak/HP | Printer 58 mm, scan QR, viewport/perangkat HP | Struk terbaca, QR valid, operasi admin tanpa scroll horizontal | PDF 58 mm/QR/viewport HP lulus; printer fisik tertunda |
| Bukti lanjutan | AC WA/loyalti/laporan/demo belum tersedia | Ditandai belum teruji E2E beserta pemilik M3–M6 | Dicatat di audit M2 |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --fail-on-warning
rtk proxy docker compose exec -T app vendor/bin/pint --test
rtk proxy docker build --target frontend -t ceklaundry-frontend -f docker/php/Dockerfile .
rtk proxy docker run --rm ceklaundry-frontend npm run lint
rtk proxy docker run --rm ceklaundry-frontend npm run typecheck
rtk proxy docker run --rm ceklaundry-frontend npm run build
rtk proxy python3 docs/audits/validate-final-specs.py
rtk git diff --check
```

Browser QA mengikuti [development](../development.md) pada DB uji setelah suite backend; catat browser/printer/QR aktual. Kegagalan gate diperbaiki sebelum DONE.

## 7. Out of Scope

1. Notifikasi otomatis M3, promo/loyalti aktif M4, laporan keuangan M5, demo/PWA M6.
2. Klaim siap produksi/deploy hanya dari CI atau browser lokal.

## 8. Completion Checklist

- [ ] Seluruh tiket M2 diotorisasi, diimplementasikan dan diverifikasi.
- [ ] Bukti AC, CI, browser, concurrency dan cetak tersedia; bukti cetak printer fisik masih tertunda.
- [ ] Dokumentasi fitur, sesi, indeks dan arsip diperbarui sesuai hasil aktual.

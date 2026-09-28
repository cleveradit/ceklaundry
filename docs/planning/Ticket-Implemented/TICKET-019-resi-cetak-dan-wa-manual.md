# Implementation Plan: TICKET-019 (Resi thermal, QR, dan WhatsApp manual)

**Ticket:** `TICKET-019`
**Status:** `DONE`
**Hasil:** [Audit M2](../../audits/m2-verification.md) memuat bukti dan batas verifikasi.
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-018`, `TICKET-017`
**Tahap:** M2 — serah resi

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [PRD 5.B/8/9.1/9.2](../../initiate-file/prd.md), [US-209/210](../../initiate-file/user-stories.md), [arsitektur 8](../../initiate-file/architecture.md) |
| Keterlacakan | US-209 AC1–4, US-210 AC1/2 (bagian M2); SEC-06/08, AND-08/25, KOM-02, UX-03, LOK-01–04 |
| Cetak | Browser print 58 mm, lebar efektif ±48 mm, QR SVG lokal berisi URL `/t/{kode}`; tanpa driver printer. |
| WA manual | `wa.me` dari nomor canonical customer dan teks ringkas; pembukaan M2 tidak diklaim berhasil terkirim/API. Log `dibuka_manual` baru M3; baca-saja tanpa tulis log. |
| Otorisasi | Pengguna menyetujui implementasi seluruh TICKET-011–020. |

## 2. Objective

Admin/owner dapat memberi struk terbaca dan membuka pesan WhatsApp manual berisi tautan status. Resi publik tetap tersamar, sedangkan identitas penuh hanya muncul pada cetak panel yang diotorisasi.

## 3. Non-Negotiable Technical Contract

1. `app/Services/ReceiptPrintService.php`: DTO internal setelah policy cabang dan DTO publik masked dari TICKET-018; QR SVG lokal memakai `bacon/bacon-qr-code` yang sudah dipatok, URL HTTPS/APP_URL terkonfigurasi tanpa aset eksternal.
2. `app/Http/Controllers/App/ReceiptPrintController.php`, `app/Http/Controllers/Public/ReceiptController.php`, `routes/web.php`: cetak panel dan cetak publik berbeda DTO; publik memakai limiter bersama.
3. `resources/views/receipts/thermal.blade.php`, `resources/css/receipt.css`: `@media print` 58 mm/±48 mm, rincian snapshot, total, terbayar/sisa, estimasi, kondisi, label batal, QR.
4. `app/Services/ManualReceiptLinkService.php`, `resources/js/Pages/App/TransactionDetail.tsx`: `wa.me/{no_hp}` encoded dengan kode, total, sisa, estimasi, link status; tanpa network call server ke WA.
5. `tests/Feature/Operations/ReceiptPrintTest.php`, `tests/Feature/Operations/ManualReceiptLinkTest.php`: policy, masking, isi QR/link, read-only.

## 4. Scope of Changes

1. Cetak internal dari panel admin cabangnya/owner bisnis; developer tidak mendapat jalur internal.
2. Cetak publik tidak merender data penuh lalu menyembunyikannya dengan CSS; header no-store/no-referrer/noindex.
3. Sediakan titik integrasi log WA manual M3 dan simulasi demo M6 tanpa memberi klaim keduanya sudah berjalan.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Thermal | Resi Rp74.500, DP Rp30.000, kondisi, QR | Rincian dan sisa Rp44.500 terbaca pada 58 mm; QR menuju status tepat | Lihat [audit M2](../../audits/m2-verification.md) |
| Privasi | Cetak publik vs panel, view source | Publik masked, internal penuh hanya setelah policy | Lihat [audit M2](../../audits/m2-verification.md) |
| WA | Customer `62812…`, status siap/batal | URL wa.me valid, pesan ringkas akurat, bukan klaim terkirim | Lihat [audit M2](../../audits/m2-verification.md) |
| Read-only | Panel yang boleh dibaca, klik link WA | Link terbuka tanpa business write; tanpa log M2 | Lihat [audit M2](../../audits/m2-verification.md) |
| Isolasi | Admin lain/developer mencoba cetak internal | 404/403, tak ada detail/QR bocor | Lihat [audit M2](../../audits/m2-verification.md) |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=ReceiptPrintTest
rtk proxy docker compose exec -T app php artisan test --filter=ManualReceiptLinkTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

Cetak fisik dan scan QR pada browser/perangkat aktual menjadi gate TICKET-020.

## 7. Out of Scope

1. Pengiriman WA API, kuota, log manual dan demo suppression M3/M6.
2. Integrasi driver printer khusus.

## 8. Completion Checklist

- [x] Otorisasi implementasi, matriks dan verifikasi selesai.
- [x] Scan QR/hasil cetak nyata dicatat dalam audit M2.

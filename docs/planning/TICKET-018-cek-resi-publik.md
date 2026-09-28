# Implementation Plan: TICKET-018 (Cek resi dan status publik)

**Ticket:** `TICKET-018`
**Status:** `DONE`
**Hasil:** [Audit M2](../audits/m2-verification.md) memuat bukti dan batas verifikasi.
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-015`
**Tahap:** M2 — pelanggan tanpa login

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [PRD 4.1/5.A/9.1/9.3](../initiate-file/prd.md), [US-201/202](../initiate-file/user-stories.md), [arsitektur 2/8](../initiate-file/architecture.md) |
| Keterlacakan | US-201 AC1–4, US-202 AC1–5; SEC-02/04/06/08, ISO-05, UX-05, LOK-01–04 |
| Capability | Kode resi trim+uppercase, tepat 6 karakter; semua jalur resolver berbagi 30/menit/IP sebelum lookup. |
| Privasi | DTO whitelist; nama maksimal 3 karakter pertama+`***` (nama pendek 1 karakter), nomor maksimal 4 awal+`***`+3 akhir; tanpa email/ID/actor/log. |
| Otorisasi | Pengguna menyetujui implementasi seluruh TICKET-011–020. |

## 2. Objective

Pelanggan dapat mengecek progres, item, total dan sisa tagihan melalui halaman depan atau URL resi tanpa login dan tanpa JavaScript. Halaman publik membatasi pencarian serta hanya memuat data yang memang boleh diketahui pemegang resi.

## 3. Non-Negotiable Technical Contract

1. `app/Services/PublicReceiptService.php`: resolver kode global terbatas, whitelist/masking server, histori status, rincian uang dan cabang; tanpa model mentah ke Blade.
2. `app/Services/ReceiptRateLimiter.php`: satu counter atomik 30/menit/IP fixed window WIB, seluruh form/direct URL/cetak publik dan jalur email M3; IP hanya dari proxy tepercaya.
3. `app/Http/Controllers/Public/ReceiptController.php`, `routes/web.php`: GET `/`, GET `/t/{kode_resi}` dan form cek; gagal non-enumeratif, header no-store/no-referrer/noindex; GET tanpa mutasi.
4. `resources/views/public/home.blade.php`, `resources/views/public/status.blade.php`, `resources/css/public.css`: input resi utama, demo berlabel belum tersedia sampai M6, timeline dan angka Indonesia.
5. `tests/Feature/Public/ReceiptLookupTest.php`, `tests/Integration/ReceiptLimiterTest.php`: DTO/HTML, lifecycle, paralel limiter dan tanpa JS.

## 4. Scope of Changes

1. Tampilkan cabang, timeline, item snapshot, potongan0 M2, total/terbayar/sisa, estimasi dan catatan kondisi; pembatalan ditandai tanpa menghapus payment.
2. Link tel/WA cabang memakai data publik cabang; jangan memuat aset pihak ketiga atau React panel.
3. NONAKTIF dan BACA_SAJA bisnis nyata tetap mengizinkan GET; demo expired menunggu M6 harus ditolak sesuai evaluator M1.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Lookup | ` k7f3xa ` via form/URL | Normalisasi, redirect/tampil `/t/K7F3XA`, timeline & sisa benar | Lihat [audit M2](../audits/m2-verification.md) |
| Tak ditemukan | Kode salah/malformed | Pesan Indonesia sama, tanpa keberadaan/email bocor | Lihat [audit M2](../audits/m2-verification.md) |
| Privasi | Inspeksi HTML, JSON, data attributes, view source | Hanya masked identity; tak ada email/ID/actor/log/full phone | Lihat [audit M2](../audits/m2-verification.md) |
| Limiter | 31 request campur form/direct/print, valid/invalid, dua proses | Request ke-31 429 ramah; jalur sama berbagi counter | Lihat [audit M2](../audits/m2-verification.md) |
| Lifecycle | Baca-saja/nonaktif, JS mati | Status tetap tersedia dan form inti berfungsi | Lihat [audit M2](../audits/m2-verification.md) |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=ReceiptLookupTest
rtk proxy docker compose exec -T app php artisan test --filter=ReceiptLimiterTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

## 7. Out of Scope

1. Form/verifikasi email publik M3; saat dibuat harus memakai limiter sama dan menguji US-202 AC4 ulang.
2. Tombol demo aktif M6 serta saldo stempel/promosi M4.

## 8. Completion Checklist

- [x] Otorisasi implementasi, matriks dan verifikasi selesai.
- [x] Privasi HTML nyata dan form tanpa JS diuji di browser TICKET-020.

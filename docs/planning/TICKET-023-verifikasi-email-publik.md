# Implementation Plan: TICKET-023 (Verifikasi email transaksi dari halaman publik)

**Ticket:** `TICKET-023`

**Status:** `READY`

**Target Audience:** AI Developer Agents

**Depends On:** `TICKET-022`

**Tahap:** M3 — email transaksi

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | US-303 AC1–8, PRD 9.1, arsitektur 6.4, skema transactions 2.8 |
| Keterlacakan | US-202 AC4–5, US-208 AC4, US-303 AC1–8, US-308 AC11–13; AND-01/04/08–10/21/27, SEC-06/09, ISO-01/05 |
| Otoritas | Kode resi hanya boleh meminta perubahan `transactions.notification_email` sesudah email baru dikonfirmasi; `customers.email` dan transaksi lain tetap. |
| Batas | Bersama limiter resi 30/menit/IP, tambahan 3/jam/resi, 10/hari/IP, 5/jam/recipient; link signed versi terbaru habis 24 jam; shared 30/menit/IP. |
| Lifecycle | Hanya DITERIMA/DIPROSES dan tenant writable; POST read-only 423; GET konfirmasi tanpa mutasi. |
| Otorisasi | Penyusunan tiket diminta pengguna; implementasi M3 belum diminta. |

## 2. Objective

Pemegang resi dapat mengusulkan alamat email untuk transaksi itu dan membuktikan kendali alamat tersebut. Email operasional tetap memakai snapshot aktif sampai konfirmasi sah.

## 3. Non-Negotiable Technical Contract

1. `app/Http/Controllers/Public/ReceiptEmailController.php`, `routes/web.php`, `resources/views/public/status.blade.php`: form POST request, GET konfirmasi, POST final; semua route memakai limiter resi dan header no-store/no-referrer/noindex.
2. `app/Services/TransactionEmailVerificationService.php`: root→customer→transaction lock, pending/version/expiry+log/job satu commit; konfirmasi signed URL hanya mengubah email transaksi dan versi.
3. `app/Jobs/SendNotification.php`: verifikasi stale, expired, ready, terminal, hold/cutoff atau demo tidak mengirim; signature/recipient tidak bocor ke log teknis.
4. `app/Services/TransactionService.php`, `app/Services/CustomerMergeService.php`: transisi ready dan merge source menggugurkan pending dalam commit yang sama.
5. `tests/Feature/Public/ReceiptEmailVerificationTest.php`, `tests/Integration/ReceiptEmailVerificationConcurrencyTest.php`: limiter, link, race dan isolasi.

## 4. Scope of Changes

1. Form publik tetap dapat dibuka tanpa JavaScript; pesan gagal tidak membocorkan email aktif/pending.
2. Batasi rate atomik juga ketika request paralel; limiter dijalankan sebelum lookup keberadaan kode. Tautan mengikat kode, versi, expiry tanpa email/ID pada URL.
3. Uji konfirmasi vs ready/lifecycle/merge dengan koneksi MySQL nyata, hasil mengikuti salah satu urutan lock sah.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Normal | DITERIMA, email baru valid, klik link terbaru lalu POST | Hanya email transaksi berubah; master/tx lain tetap | `[ ]` |
| Batas | Request ke-4/jam/resi atau ke-11/hari/IP, paralel | 429; active email dan job tidak bertambah | `[ ]` |
| Stale | Link lama/terpakai/expired, ready atau merge source | Ditolak tanpa mutasi/kiriman | `[ ]` |
| Lifecycle | Read-only/nonaktif, GET/POST | GET tanpa side effect; POST 423 sesuai kontrak | `[ ]` |
| Privasi | Inspect HTML/URL/log/response gagal | Tidak ada email aktif/pending, ID atau signature log | `[ ]` |
| Race | Konfirmasi bersamaan transisi ready | Satu urutan sah; ready memakai snapshot email yang sudah aktif pada urutan itu | `[ ]` |

## 6. Verification Commands

`rtk proxy docker compose exec -T app php artisan test --filter=ReceiptEmailVerificationTest`

`rtk proxy docker compose exec -T app php artisan test --filter=ReceiptEmailVerificationConcurrencyTest`

`rtk git diff --check`

## 7. Out of Scope

1. Penyuntingan email master customer dari halaman publik.
2. Ready/reminder otomatis dan WA API.

## 8. Completion Checklist

- [ ] Status READY setelah pengguna meminta implementasi.
- [ ] AC publik, limiter, lifecycle dan race lulus.
- [ ] Tidak ada mutasi GET atau pembocoran email.

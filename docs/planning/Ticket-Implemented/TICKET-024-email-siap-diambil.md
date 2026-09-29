# Implementation Plan: TICKET-024 (Email otomatis siap diambil)

**Ticket:** `TICKET-024`

**Status:** `DONE`

**Hasil:** implementasi dan verifikasi dicatat pada [audit M3](../../audits/m3-verification.md). Matriks di bawah adalah rencana pengujian awal; audit mencatat tingkat bukti aktual dan batas staging per AC.

**Target Audience:** AI Developer Agents

**Depends On:** `TICKET-022`, `TICKET-023`

**Tahap:** M3 — notifikasi ready

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | US-301 AC1–5, PRD 5.G/9.2, arsitektur 6.1–6.2 |
| Keterlacakan | US-301 AC1–5, US-303 AC6–8, US-308 AC2–4/6–13; AND-01/03/04/08/10/21/27, LOK-01–03 |
| Identitas | Satu key `tx:{id}:ready:email:0` per transaksi; email hanya jika snapshot `notification_email` aktif ada; email dan WA independen. |
| Isi | Nama laundry/cabang, resi, total, sisa bila ada, link status; rupiah bulat dan bahasa Indonesia. |
| Otorisasi | Pengguna menyetujui implementasi seluruh TICKET-021–029 pada 29 September 2026. |

## 2. Objective

Pelanggan yang memiliki email transaksi menerima pemberitahuan setelah cucian siap, tanpa membuat transisi status bergantung pada kesehatan SMTP.

## 3. Non-Negotiable Technical Contract

1. `app/Services/TransactionService.php`, `app/Http/Controllers/App/TransactionStatusController.php`: transisi ke SIAP_DIAMBIL mereservasi log+job email pada transaksi domain yang sama; reaksi hanya pada transisi baru, bukan no-op retry.
2. `app/Services/NotificationDispatcher.php`, `app/Jobs/SendNotification.php`: preflight state/lifecycle/email/hold/cutoff sebelum marker; email snapshot tujuan tetap; payload dihitung dari data terbaru sebelum panggilan pertama dan dibekukan untuk retry pasti ditolak.
3. `resources/views/emails/ready.blade.php`, `tests/Feature/Notification/ReadyEmailTest.php`: isi, lokalisasi, key, skip dan independensi kanal.

## 4. Scope of Changes

1. Saat email kosong, status tetap berhasil tanpa log email dan tanpa error.
2. Saat provider gagal/unknown, status transaksi tetap SIAP_DIAMBIL; log menunjukkan hasil nyata dan tidak mengirim ulang unknown.
3. Revalidasi pickup, cancel, read-only, email version dan restore sebelum call; pesan in-flight yang sudah diotorisasi tidak diklaim dapat ditarik.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Normal | DITERIMA→DIPROSES→SIAP, email ada | Satu accepted email berisi kode, total, sisa, link; log berhasil | `[ ]` |
| Kosong/retry | Email null; status request sama diulang | Tidak ada email null; tidak ada log/send duplikat | `[ ]` |
| Gagal | SMTP definite reject atau timeout | Transisi tetap commit; retry terbatas atau review unknown | `[ ]` |
| Kondisi berubah | Job menunggu lalu pickup/cancel/read-only | dilewati_kondisi tanpa SMTP call | `[ ]` |
| Restore | Ready historis sebelum cutoff dan hold dilepas | Tidak catch-up; ready baru sesudah cutoff eligible | `[ ]` |

## 6. Verification Commands

`rtk proxy docker compose exec -T app php artisan test --filter=ReadyEmailTest`

`rtk proxy docker compose exec -T app php artisan test --filter=TransactionLifecycleTest`

`rtk git diff --check`

## 7. Out of Scope

1. Pengingat terjadwal dan WA API.
2. Email yang dijamin diterima/dibaca pelanggan; accepted hanya berarti diterima provider.

## 8. Completion Checklist

- [x] Implementasi, pengujian QA/CI, dan batas bukti dicatat pada [audit M3](../../audits/m3-verification.md).

# Implementation Plan: TICKET-025 (Pengingat otomatis terjadwal)

**Ticket:** `TICKET-025`

**Status:** `DONE`

**Hasil:** implementasi dan verifikasi dicatat pada [audit M3](../../audits/m3-verification.md). Matriks di bawah adalah rencana pengujian awal; audit mencatat tingkat bukti aktual dan batas staging per AC.

**Target Audience:** AI Developer Agents

**Depends On:** `TICKET-021`, `TICKET-022`, `TICKET-024`

**Tahap:** M3 — pengingat

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | US-302 AC1–10, PRD 9.2, arsitektur 6.1/7, skema transactions 2.8 |
| Keterlacakan | US-302 AC1–10, US-306 AC1–2, US-308 AC8/10–13; AND-04/10/20/21/27, LOK-03 |
| Jadwal | Harian 08.00 WIB; pertama sejak `waktu_siap_diambil + N×24 jam`, berikutnya sejak `last_reminder_at + M×24 jam`; default N=2/M=2/K=3. |
| Cursor | `reminder_count`/`last_reminder_at` berubah saat satu nomor direservasi, bukan setelah sukses; satu nomor per transaksi per putaran, tidak burst saat scheduler pulih. |
| Kanal | Tanpa kanal eligible cursor tetap; setelah integrasi TICKET-027, WA eligible penuh menghabiskan nomor dengan log `dilewati_batas`; manual tidak mengubah cursor. |
| Otorisasi | Pengguna menyetujui implementasi seluruh TICKET-021–029 pada 29 September 2026. |

## 2. Objective

Pelanggan menerima pengingat berkala hanya selama cucian masih siap diambil. Scheduler yang overlap atau sempat berhenti tetap menghasilkan nomor pengingat yang konsisten.

## 3. Non-Negotiable Technical Contract

1. `app/Services/ReminderScheduler.php`, `routes/console.php`: scan harian 08.00 WIB, recheck di bawah root lock, reservasi satu nomor/tx/putaran dengan log+job atomik.
2. `app/Services/NotificationDispatcher.php`, `app/Jobs/SendNotification.php`: key `tx:{id}:reminder:{channel}:{n}`, preflight status/lifecycle/saklar/cutoff, email independen dari WA.
3. `app/Models/Transaction.php`, `app/Models/BusinessSetting.php`: cursor persisten tidak reset saat N/M/K atau switch berubah; K terkini membatasi nomor baru.
4. `tests/Integration/ReminderSchedulerTest.php`, `tests/Feature/Notification/ReminderLifecycleTest.php`: MySQL dua proses, waktu WIB, overlap/outage dan perubahan settings.

## 4. Scope of Changes

1. Gunakan kanal email aktif; reservasi WA tetap nonaktif sampai TICKET-027 membuktikan kuota/recipient guard.
2. Tutup pending otomatis saat pickup/cancel, bisnis baca-saja/nonaktif, pengingat off/K turun. Off→on atau expired→aktif sebelum worker jalan tidak membangunkan pending lama.
3. Selama hold/cutoff tidak membuat cursor, slot atau log catch-up untuk `waktu_siap_diambil` historis; scheduler pulih tidak mengirim rentetan nomor.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Jadwal | Ready H0; N=2/M=2/K=3; scheduler H2/H4/H6/H8 | Nomor 1/2/3 saja, H8 tidak menambah | `[ ]` |
| Overlap/outage | Dua scheduler H2, lalu berhenti sampai H8 | Satu nomor per putaran, tidak burst 1–3 | `[ ]` |
| Kanal | Email null dan WA off; email ada | Tanpa kanal cursor tetap; email eligible mereservasi satu nomor | `[ ]` |
| Settings/lifecycle | N/M/K berubah, off→on cepat, pickup sebelum job | Aturan baru untuk nomor baru; pending lama ditutup tanpa send | `[ ]` |
| Restore | Ready sebelum/saat hold, scheduler setelah cutoff | Tidak ada reservasi/cursor/catch-up untuk ready historis | `[ ]` |

## 6. Verification Commands

`rtk proxy docker compose exec -T app php artisan test --filter=ReminderSchedulerTest`

`rtk proxy docker compose exec -T app php artisan test --filter=ReminderLifecycleTest`

`rtk git diff --check`

## 7. Out of Scope

1. WA provider dan kuota penuh end to end; TICKET-027 menutup US-302 AC7 untuk WA eligible penuh.
2. Kiriman manual atau reset cursor melalui UI.

## 8. Completion Checklist

- [x] Implementasi, pengujian QA/CI, dan batas bukti dicatat pada [audit M3](../../audits/m3-verification.md).

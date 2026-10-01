# Implementation Plan: TICKET-046 (Simulasi komunikasi demo)

**Ticket:** `TICKET-046`

**Status:** `REVIEW`

**Target Audience:** AI Developer Agents

**Depends On:** `TICKET-044`

**Tahap:** M6 — US-603

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M6](../plan.md#10-m6--demo-dan-pwa), [PRD 9.2/9.3/9.4](../initiate-file/prd.md), [US-603](../initiate-file/user-stories.md), [arsitektur 6.4/7](../initiate-file/architecture.md) |
| Outbound | Tidak ada SMTP, API WA, `wa.me`, atau `tel:` yang dapat dipakai demo; guard transport tetap menjadi lapis terakhir meski service pemanggil salah. |
| Log | Kanal otomatis yang benar-benar eligible dicatat masing-masing sebagai `ditekan_demo` dengan tipe, kanal, notification_key, dan snapshot tujuan; kanal tak eligible tidak membuat log. Simulasi manual memakai tipe/kanal/key/tujuan yang sesuai dan tidak mengonsumsi kuota API WA. |
| Akun/verifikasi | Reset akun demo memberi respons generik tanpa token/job/send; verifikasi email transaksi hanya simulasi dan tidak mengaktifkan email tanpa verifikasi nyata. |
| Keterlacakan | FR-M04; US-603 AC 1–2; AND-12/25, SEC-04, ISO-05, DAT-03, LOK-01. |
| Otorisasi | Pengguna meminta penyusunan tiket M6; implementasi belum diminta. |

## 2. Objective

Semua fitur komunikasi tetap dapat dicoba di demo melalui hasil simulasi yang terlihat, tetapi tidak menghasilkan kontak eksternal. Jejak notifikasi transaksi tetap cukup untuk memeriksa kanal, tujuan, dan deduplikasi tanpa menyatakan pesan berhasil dikirim.

## 3. Non-Negotiable Technical Contract

1. `app/Services/NotificationDispatcher.php`, `app/Services/ReminderScheduler.php`: setelah eligibility per kanal diperiksa, business demo membuat log terminal `ditekan_demo` tanpa queue job, attempt, atau reservasi kuota. Existing `OutboundGuard` tidak boleh memotong jalur demo sebelum log simulasi dibentuk.
2. `app/Services/ManualNotificationService.php`, `app/Services/ManualReceiptLinkService.php`, `app/Services/TransactionEmailVerificationService.php`: aksi manual dan verifikasi demo menghasilkan preview/log yang sesuai, tanpa URL `wa.me` atau request verifikasi yang mengaktifkan email.
3. `app/Services/AccountService.php`, `app/Jobs/SendPasswordReset.php`, `app/Services/NotificationTransport.php`, `app/Services/OutboundGuard.php`: reset akun reserved maupun akun demo tambahan tidak menghasilkan token/job/send; transport dan retry tetap menolak business demo walau ada job lama.
4. `resources/views/public/status.blade.php`, `app/Services/PublicReceiptService.php`, `resources/js/Layouts/AppLayout.tsx`: hapus/nonaktifkan semua tautan komunikasi keluar pada tampilan demo, termasuk `tel:` pada status publik; tampilkan keterangan simulasi berbahasa Indonesia.
5. `tests/Feature/DemoNotificationTest.php`, `tests/Integration/DemoOutboundGuardTest.php`: fake SMTP/HTTP menangkap nol panggilan pada seluruh jalur, sekaligus memeriksa log dan key unik.

## 4. Scope of Changes

1. Pisahkan keputusan simulasi demo dari restore hold dan lifecycle bisnis nyata. Pada status siap dan reminder, evaluasi eligibility email/WA dahulu lalu tulis satu log terminal per kanal eligible; duplicate request/scheduler tidak membuat log ganda.
2. Ubah aksi manual email, WA resi/pengingat, dan verifikasi email transaksi menjadi preview/log demo yang tidak membuka link atau mengirim jaringan. Jalur yang tidak eligible tetap tidak menghasilkan log.
3. Pertahankan respons lupa password yang tidak membocorkan akun, tanpa menyimpan token reset demo; blokir password change/reset akun reserved sesuai TICKET-045.
4. Periksa seluruh tombol/tautan `wa.me`/`tel:` dari panel, halaman publik, dan cetak untuk tenant demo; transport guard tetap fail closed pada worker/retry.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Dua kanal eligible | Transaksi demo siap dengan email dan WA aktif | Dua log terpisah `ditekan_demo`; nol job/send; kuota WA tidak berubah | `[ ]` |
| Kanal tak eligible | Email kosong atau WA nonaktif | Hanya kanal eligible dicatat | `[ ]` |
| Manual | Klik email, resi WA, pengingat WA atau telepon | Preview/simulasi tanpa SMTP, HTTP, `wa.me`, atau `tel:` keluar | `[ ]` |
| Verifikasi | Minta/konfirmasi email transaksi demo | Tidak ada email nyata; email tidak menjadi verified secara semu | `[ ]` |
| Keamanan akun | Minta reset akun demo reserved/tambahan | Respons generik, tanpa token, queue job, atau send | `[ ]` |
| Retry/gagal | Job lama/fake payload demo mencapai worker/transport | Ditolak sebelum provider call; tidak mengubah log menjadi berhasil | `[ ]` |
| Deduplikasi | Request/scheduler sama diulang | Key log tetap unik dan jumlah log sama | `[ ]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=DemoNotificationTest`
2. `rtk proxy docker compose exec -T app php artisan test --filter=DemoOutboundGuardTest`
3. `rtk proxy docker compose exec -T app php artisan test --filter=AuthResetQueueTest`
4. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`

Expected: nol panggilan transport eksternal dengan log simulasi yang lengkap; regresi jalur nyata dan restore hold tetap lulus.

## 7. Out of Scope

1. Mengubah semantik accepted/retry provider untuk tenant nyata.
2. Menganggap reset akun sebagai notification_log transaksi; reset demo tidak membuat token atau job.

## 8. Completion Checklist

- [ ] Otorisasi implementasi diterima dan status menjadi `READY`.
- [ ] Semua jalur komunikasi demo terpetakan dan disimulasikan.
- [ ] Log eligible, nol outbound, dan deduplikasi terbukti.
- [ ] Jalur tenant nyata serta restore hold tidak regresi.

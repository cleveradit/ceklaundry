# Implementation Plan: TICKET-028 (Pengingat manual dan riwayat notifikasi)

**Ticket:** `TICKET-028`

**Status:** `READY`

**Target Audience:** AI Developer Agents

**Depends On:** `TICKET-022`, `TICKET-024`, `TICKET-025`, `TICKET-027`

**Tahap:** M3 — operasional notifikasi

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | US-304 AC1–4, US-308 AC1/4–5, US-210 AC2, PRD 9.2, arsitektur 6.4 |
| Keterlacakan | US-210 AC2, US-304 AC1–4, US-308 AC1/4–5/11–13; AND-04/21/25/27, ISO-01/02/03, UX-04, LOK-01 |
| Manual email | Hanya SIAP_DIAMBIL, UUID+hash persisten, 1 permintaan baru/10 menit/tx dan 20/hari/user; key sama replay, key baru setelah unknown perlu konfirmasi risiko. |
| Manual WA | `wa.me` hanya berarti link dibuka, status `dibuka_manual`; tidak dihitung sebagai sukses/kuota WA API. Baca-saja membuka tanpa log. |
| Demo | Perilaku preview dan `ditekan_demo` menjadi integrasi M6; M3 menegakkan transport guard pusat dan tidak membuat outbound nyata pada tenant demo. |
| Otorisasi | Penyusunan tiket diminta pengguna; implementasi M3 belum diminta. |

## 2. Objective

Admin dapat menghubungi pelanggan secara sadar ketika cucian siap, melihat riwayat per kanal, dan membedakan pesan accepted dari link manual yang hanya dibuka atau kiriman dengan hasil belum pasti.

## 3. Non-Negotiable Technical Contract

1. `app/Services/ManualNotificationService.php`, `app/Http/Controllers/App/ManualNotificationController.php`, `routes/web.php`: email manual idempoten UUID+hash, cooldown dan limit atomik; `wa.me` log dahulu pada writable, lalu URL dibuka.
2. `app/Services/ManualReceiptLinkService.php`, `app/Http/Controllers/App/TransactionController.php`, `resources/js/Pages/App/TransactionDetail.tsx`: resi manual M2 memperoleh log `whatsapp_manual/resi/dibuka_manual`; reminder manual email/WA dan peringatan unknown tampil dengan teks jelas.
3. `app/Models/NotificationLog.php`, `app/Services/NotificationLogPresenter.php`: detail log owner/admin terscope cabang, tujuan dan reason aman; developer hanya metadata agregat yang diizinkan.
4. `tests/Feature/Notification/ManualNotificationTest.php`, `tests/Integration/ManualNotificationConcurrencyTest.php`: double-click, cooldown, unknown, read-only, cabang dan log.

## 4. Scope of Changes

1. Email manual memakai mesin TICKET-022 dan tidak mengubah reminder_count. Retry key sama menampilkan hasil lama; payload beda dengan UUID sama 409.
2. Link resi manual pada semua status mengikuti aturan M2; reminder manual hanya SIAP_DIAMBIL. Tidak mengklaim penerima benar-benar menerima pesan wa.me.
3. Pada read-only, email manual ditolak; link WA dari DTO terotorisasi boleh terbuka tanpa write. Hold memblokir outbound dan tidak membuat backlog.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Email | SIAP, dua klik UUID sama/response hilang | Satu log/key/job/send; replay hasil sama | `[ ]` |
| Batas | UUID baru <10 menit atau >20/hari/user | Ditolak atomik; tidak mengubah cursor/queue | `[ ]` |
| Unknown | Kiriman lama perlu_pemeriksaan, UUID baru | UI meminta konfirmasi sadar risiko sebelum kontak baru | `[ ]` |
| WA | Buka resi/pengingat wa.me lalu batal di aplikasi WA | Log dibuka_manual saja; kuota API dan berhasil tetap | `[ ]` |
| Lifecycle/isolasi | Belum siap, read-only, admin cabang lain, hold | Email ditolak, read-only WA tanpa log, lintas cabang 404, hold tanpa send | `[ ]` |
| Log | Email accepted, WA unknown, WA manual | Kanal/tipe/tujuan/status/waktu tampil akurat tanpa credential | `[ ]` |

## 6. Verification Commands

`rtk proxy docker compose exec -T app php artisan test --filter=ManualNotificationTest`

`rtk proxy docker compose exec -T app php artisan test --filter=ManualNotificationConcurrencyTest`

`rtk git diff --check`

## 7. Out of Scope

1. Mengklaim status `berhasil` dari pembukaan wa.me.
2. Preview UI demo lengkap; M6 memverifikasinya end to end.

## 8. Completion Checklist

- [ ] Status READY setelah pengguna meminta implementasi.
- [ ] Limit/idempotensi/race dan akses cabang lulus.
- [ ] Log membedakan accepted, unknown, skipped dan dibuka_manual.

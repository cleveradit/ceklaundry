# Implementation Plan: TICKET-044 (Provisioning dan fixture demo)

**Ticket:** `TICKET-044`

**Status:** `DONE`

**Target Audience:** AI Developer Agents

**Depends On:** TICKET-001–043 (M1–M5 selesai)

**Tahap:** M6 — US-601, US-604 AC 2

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M6](../../plan.md#10-m6--demo-dan-pwa), [PRD 9.4](../../initiate-file/prd.md), [US-601 dan US-604](../../initiate-file/user-stories.md), [arsitektur 7–8](../../initiate-file/architecture.md) |
| Akses | `POST /demo` dari tombol Coba Demo memakai CSRF; prospek langsung memperoleh sesi owner demo. Login dengan kredensial akun reserved tidak tersedia. |
| Fixture | Tepat 2 cabang, 3 layanan master yang tersedia di cabang, 1 owner, 1 admin Cabang Utama, 6 customer, 15 transaksi dengan status/payment/ledger dan timestamp sesuai PRD 9.4; promo dan loyalti aktif, DP aktif. Identitas sintetis memakai domain `.invalid` dan nomor sintetis. |
| Batas | Maksimal 3 demo per IP per hari WIB, fixed window dan increment atomik termasuk request paralel; IP berasal dari trusted proxy yang dikonfigurasi. |
| Keterlacakan | FR-M01/M02/M06; US-601 AC 1–3, US-604 AC 2; SEC-02/04, ISO-01/05, PLH-03, AND-12, LOK-01. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-044–049 pada 1 Oktober 2026. |

## 2. Objective

Prospek dapat membuat satu tenant demo utuh dari halaman depan dan langsung mencoba alur M1–M5. Pembuatan harus atomik, tenant saling terisolasi, dan fixture konsisten dengan aturan bisnis tanpa pengiriman eksternal.

## 3. Non-Negotiable Technical Contract

1. `routes/web.php`, `app/Http/Controllers/Public/DemoController.php`, `resources/views/public/home.blade.php`: aktifkan tombol dan `POST /demo` dengan CSRF; respons sukses mengautentikasi owner reserved dan menuju panel, kegagalan tidak meninggalkan tenant setengah jadi.
2. `app/Services/DemoProvisioner.php`, `database/seeders/DemoFixtureSeeder.php`: buat business demo dan fixture PRD 9.4 dalam transaksi MySQL; gunakan service domain/snapshot yang berlaku, timestamp terurut, ledger berasal dari transaksi, dan tidak membuat outbound nyata.
3. `app/Services/DemoRateLimiter.php`: batas 3/hari/IP dengan hari WIB dan operasi check+increment atomik pada store database; 429 berbahasa Indonesia; jangan menyimpan IP mentah di key/log.
4. `app/Models/Business.php` dan migrasi M1 yang sudah ada: pakai `is_demo`/`demo_expires_at` existing (`created_at + 7×24 jam`); jangan menambah migrasi bila kontrak skema saat ini cukup.
5. `tests/Feature/DemoProvisionTest.php`, `tests/Integration/DemoProvisionConcurrencyTest.php`: cocokkan fixture persis, rollback gagal, dua tenant terpisah, dan batas paralel MySQL nyata.

## 4. Scope of Changes

1. Hubungkan tombol yang saat ini disabled ke form POST demo; tampilkan keadaan loading dan pesan 429/kegagalan yang dapat dimengerti.
2. Provision business, settings, cabang, akun reserved, layanan, promo, loyalti, customer, transaksi, payment, status history, dan ledger dalam satu unit yang dapat di-rollback. Gunakan konfigurasi sintetis yang memungkinkan simulasi komunikasi pada TICKET-046 tanpa membuka akses jaringan.
3. Pastikan 10 transaksi lunas customer pertama tersebar lima per cabang; lima customer lain masing-masing mewakili status/DP/lunas/batal sesuai PRD 9.4. Periksa saldo stempel dari ledger dan nominal dari payment, bukan angka cache yang diisi sendiri.
4. Batasi pembuatan berdasarkan IP terpercaya dan tanggal WIB. Rate limit tetap berlaku saat request paralel, tanpa mengubah limiter resi/login.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Provision normal | Prospek tekan Coba Demo | Tenant baru, fixture persis PRD 9.4, sesi owner aktif | `[x]` |
| Isolasi | Dua prospek membuat demo | Business berbeda; transaksi/customer tenant lain 404 atau tidak muncul | `[x]` |
| Konsistensi fixture | Periksa 15 transaksi, pembayaran, promo, minimum, ledger | Status, total, payment dan saldo cocok; tidak ada outbound | `[x]` |
| Batas WIB | IP yang sama meminta demo ke-1/2/3/4, termasuk pergantian hari | Tiga berhasil, keempat 429; hari WIB berikutnya kembali tersedia | `[x]` |
| Race | Empat request serentak dari IP sama pada dua koneksi MySQL | Paling banyak tiga demo terbuat | `[x]` |
| Kegagalan | Injeksi error di tengah fixture | Seluruh data tenant di-rollback; tidak ada sesi demo setengah jadi | `[x]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=DemoFlowTest`
2. `rtk proxy docker compose exec -T app php artisan test --filter=DemoConcurrencyTest`
3. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`
4. `rtk proxy python3 docs/audits/validate-final-specs.py`

Expected: fixture dan batas lulus di MySQL 8.4, tanpa efek pada tenant nyata; catat hasil aktual saat eksekusi.

## 7. Out of Scope

1. Ganti peran dan banner demo (TICKET-045).
2. Simulasi lengkap log outbound (TICKET-046), purge (TICKET-047), dan PWA (TICKET-048).

## 8. Completion Checklist

- [x] Otorisasi implementasi diterima; status `DONE` setelah verifikasi lokal/CI.
- [x] Kontrak provisioning, fixture, dan limiter diimplementasikan.
- [x] Seluruh kasus penerimaan dan perintah verifikasi lulus.
- [x] Tidak ada pengiriman eksternal atau data lintas tenant.

Bukti aktual dan batas pengujian perangkat ada di [audit M6](../../audits/m6-verification.md). Status DONE mencakup implementasi dan gate lokal/CI; pemasangan serta splash Android/iOS fisik tetap belum diverifikasi.

# Implementation Plan: TICKET-005 (Bootstrap developer dan autentikasi)

**Ticket:** `TICKET-005`
**Status:** `DONE`
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-004`
**Tahap:** Urutan 1 — M1, pekerjaan 4–5

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [PRD](../../initiate-file/prd.md) 9.2.1/9.3, [arsitektur](../../initiate-file/architecture.md) 2/6.4/8/9 |
| Keterlacakan | US-101 AC1–9; fondasi reset US-103 AC2, US-106 AC3–5; SEC-01–SEC-05, SEC-07, SEC-08, ISO-05, AND-27, PLH-03, PLH-04, LOK-01 |
| Password | bcrypt, minimal12 karakter, maksimal72 byte UTF-8; awal/reset operator wajib ganti; tanpa remember-me/signup |
| Reset email | Token60 menit sekali pakai; SMTP global via encrypted auth job, tries1; bukan notification_logs transaksi |
| Bootstrap | Input rahasia tersembunyi atau secret injection; tidak ada password default di repository, argumen CLI atau output |
| Otorisasi | Pengguna menyetujui dan meminta implementasi seluruh TICKET-001–010 pada 28 September 2026. |

## 2. Objective

Developer dapat membuat akun awal secara aman; ketiga peran dapat login, logout dan mengelola keamanan akun sesuai lifecycle. Reset email tetap aman terhadap job usang, demo, dan pemulihan database.

## 3. Non-Negotiable Technical Contract

1. `app/Console/Commands/BootstrapDeveloper.php`, `database/seeders/DeveloperSeeder.php`: command `app:bootstrap-developer` memakai prompt password tersembunyi; seeder menerima secret injection yang terdokumentasi. Tidak otomatis mengganti password akun existing, tidak menanam kredensial fixture di DB nyata.
2. `app/Services/AccountService.php`, `app/Http/Controllers/Auth/`, `app/Http/Requests/Auth/`, `routes/auth.php`, `resources/js/Pages/Auth/`: login/logout/ganti/lupa/reset, pesan Indonesia; `/dev`, `/owner`, `/app` sesuai peran. Seluruh POST CSRF.
3. `app/Http/Middleware/RequirePasswordChange.php`: flag awal membatasi panel ke ganti password/logout; halaman publik tetap terbuka. Keamanan akun terpisah dari business-write423.
4. `app/Services/AuthRateLimiter.php`: limiter database fixed-window WIB, check+increment atomik: login5/menit/email+IP dan30/menit/IP; reset3/jam/email dan10/jam/IP. Proxy terpercaya eksplisit; identitas di key di-hash.
5. `app/Jobs/SendPasswordReset.php`, `app/Services/OutboundGuard.php`, `config/outbound.php`: ShouldBeEncrypted, token+job satu transaksi DB, cek token/email/expiry/demo saat worker berjalan, satu SMTP call global tanpa retry otomatis. Guard hold/cutoff sebelum token/job dan transport; cutoff request dari created_at token, bukan enqueue baru.
6. `app/Services/AccountService.php`: ganti password regenerasi sesi, cabut sesi lain dan reset token; reset link cabut semua sesi dan minta login ulang. Reset operator set must_change_password, cabut sesi/token dan audit atomik tanpa password. Perubahan email mencabut sesi dan token alamat lama/baru.
7. `tests/Feature/Auth/AuthenticationTest.php`, `tests/Integration/AuthResetQueueTest.php`: test mail sink/fake transport terkendali; exception/failed job sanitized, payload auth tidak terekspos panel developer.

## 4. Scope of Changes

### A. Bootstrap dan sesi

1. Buat akun developer pertama tanpa bisnis/cabang; input email trim/lowercase dan unik global.
2. Baca ulang role/akun/cabang/lifecycle setiap request; gunakan root lock TICKET-004 untuk akun tenant dan row user lock untuk developer global.
3. Terapkan cookie HttpOnly/Secure produksi/SameSite=Lax, no-store pada auth/panel, dan error aman.

### B. Keamanan password dan email

1. Password awal user fixture/onboarding diberi flag wajib ganti; reset tidak mengaktifkan akun/tenant/cabang nonaktif.
2. Reset request selalu generik; selama hold tidak membuat token/job. Demo tidak menerima token/email reset nyata.
3. Job lama setelah token diganti, password/email berubah, expiry atau cutoff dilewati tanpa SMTP. Catat prosedur membuang job/token auth lama setelah restore di `docs/development.md`.
4. UI reset owner/admin melalui operator dipasang pada TICKET-006/007; primitive keamanan dan audit diuji di sini.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Login normal | Tiga role aktif, password benar | Panel tepat, session ID diregenerasi | `[x]` |
| Wajib ganti + read-only | Owner BACA_SAJA dengan flag awal | Ganti/logout boleh; business-write423; panel lain terblokir hingga ganti | `[x]` |
| Batas password | 11/12 karakter, 72/73 byte multibyte | Batas minimum/maksimum benar; tidak memotong password | `[x]` |
| Sesi usang | User/cabang/bisnis dinonaktifkan | Request panel berikutnya ditolak; logout tetap tersedia | `[x]` |
| Reset sekali pakai | Token baru, replay, tepat60 menit | Token valid sekali; replay/expired ditolak; seluruh sesi dicabut | `[x]` |
| Email/job usang | Dua request reset lalu worker job lama | Hanya token terbaru eligible; tanpa kebocoran token/recipient | `[x]` |
| Hold/demo/cutoff | Hold aktif; demo; token request sebelum/sama cutoff | Nol SMTP; hold/demo tanpa token/job baru; request baru setelah cutoff tetap divalidasi | `[x]` |
| Rate limit paralel | Request ke-6 login/email+IP dalam window sama | Maksimal5 lolos limiter; selebihnya429, batas IP juga bekerja | `[x]` |
| SMTP gagal/ambigu | Error setelah satu panggilan transport | Respons request generik; exception aman; tidak retry otomatis | `[x]` |
| Bootstrap ulang | Email developer existing | Tidak menimpa akun/password secara diam-diam | `[x]` |

## 6. Verification Commands

Target sesudah implementasi; bootstrap dijalankan interaktif dengan rahasia lokal, tidak dicatat di log:

```bash
rtk proxy docker compose exec app php artisan app:bootstrap-developer
rtk proxy docker compose exec -T app php artisan test --filter=AuthenticationTest
rtk proxy docker compose exec -T app php artisan test --filter=AuthResetQueueTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk proxy docker run --rm ceklaundry-frontend npm run typecheck
rtk git diff --check
```

Bangun ulang image frontend memakai perintah TICKET-002 setelah perubahan UI. Uji atomisitas token/job dan limiter dengan MySQL8.4; browser mengecek redirect, CSRF, cookie, ganti password dan reset memakai mail sink. Jangan mengirim email kepada pihak nyata untuk pengujian.

## 7. Out of Scope

1. SMTP per bisnis, WA, notification_logs transaksi dan recovery notifikasi M3.
2. UI demo/provision/purge, signup publik, MFA atau perubahan metode autentikasi.
3. Klaim restore domain lengkap; di sini hanya guard dan cleanup email keamanan akun.

## 8. Completion Checklist

- [x] Lingkup diotorisasi dan dependensi selesai.
- [x] Bootstrap, seluruh AC US-101 dan primitive reset operator teruji.
- [x] Batas password/session/rate limit/hold/cutoff/demo dan queue atomik lulus.
- [x] Dokumentasi memuat cara bootstrap dan reset aman tanpa kredensial.
- [x] Feature docs, sesi dan status diperbarui sesuai bukti aktual.

## Hasil implementasi dan verifikasi

Selesai pada 28 September 2026 sesuai otorisasi pengguna. Bukti rinci, matriks per AC dan batas integrasi M2–M6 ada di [audit M1](../../audits/m1-verification.md); perintah aktual di [development](../../development.md).

CI final: [36350950049](https://github.com/cleveradit/ceklaundry/actions/runs/36350950049), 52 tes/306 assertions, quality/build/MySQL/browser lulus. Status checklist berlaku untuk lingkup M1; consumer masa depan tidak diklaim lulus E2E. Kasus kegagalan diuji melalui fault/guard dan setup bersih.

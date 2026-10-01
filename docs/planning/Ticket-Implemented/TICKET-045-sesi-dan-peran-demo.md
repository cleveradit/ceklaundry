# Implementation Plan: TICKET-045 (Sesi, banner, dan peran demo)

**Ticket:** `TICKET-045`

**Status:** `DONE`

**Target Audience:** AI Developer Agents

**Depends On:** `TICKET-044`

**Tahap:** M6 — US-602

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M6](../../plan.md#10-m6--demo-dan-pwa), [PRD 9.3–9.4](../../initiate-file/prd.md), [US-602](../../initiate-file/user-stories.md), [arsitektur 7](../../initiate-file/architecture.md) |
| Peran | `POST /demo/role` hanya untuk sesi demo; pasangan owner/admin dan tenant disimpan server, bukan diterima sebagai arbitrary user/branch ID. Switch meregenerasi session ID. |
| Batas cabang | Admin reserved selalu Cabang Utama dan hanya dapat membaca/menulis data cabang itu; owner melihat kedua cabang. Akun reserved serta Cabang Utama tidak dapat dinonaktifkan atau dipindah. |
| Tampilan | Semua halaman panel demo menampilkan banner `MODE DEMO` dan aksi `Lihat sebagai Admin` atau `Kembali sebagai Owner`. |
| Keterlacakan | FR-M03; US-602 AC 1–4; AND-12, ISO-01/02/05, SEC-04, LOK-01. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-044–049 pada 1 Oktober 2026. |

## 2. Objective

Prospek dapat berpindah antara sudut pandang owner dan admin pada tenant demo yang sama tanpa logout. Pergantian peran tidak boleh membuka ID akun, cabang, atau tenant lain melalui parameter, sesi lama, maupun manipulasi URL.

## 3. Non-Negotiable Technical Contract

1. `routes/web.php`, `app/Http/Controllers/Public/DemoRoleController.php`, `app/Services/DemoSessionService.php`: endpoint POST bersesi+CSRF, pasangan akun dari metadata sesi yang ditulis server, validasi ulang tenant dan cabang, regenerasi sesi pada switch.
2. `app/Http/Middleware/ResolveTenant.php`, `app/Http/Middleware/EnsureBusinessAccess.php`, `app/Services/LifecycleService.php`: pada setiap request verifikasi role, business demo belum kedaluwarsa, akun reserved aktif, dan admin masih pada Cabang Utama; sesi invalid ditolak tanpa kebocoran lintas tenant.
3. `app/Services/AccountService.php`, `app/Services/BranchService.php`, `app/Http/Controllers/Auth/AuthenticationController.php`: akun reserved tidak menerima login kredensial, ganti/reset password atau deaktivasi/pemindahan; Cabang Utama tidak dapat dinonaktifkan saat demo. Akun/cabang tambahan tetap mengikuti CRUD yang sah.
4. `resources/js/Components/BusinessLifecycleBanner.tsx`, `resources/js/Layouts/AppLayout.tsx`, `resources/js/types.ts`: banner eksplisit `MODE DEMO` dan tombol role switch pada seluruh halaman panel demo; tidak tampil untuk bisnis nyata.
5. `tests/Feature/DemoRoleTest.php`, `tests/browser/m6.cjs`: uji sesi, isolasi cabang/tenant, dan tampilan saat switch.

## 4. Scope of Changes

1. Tambahkan metadata sesi server saat provision: tenant dan ID pasangan akun reserved. Saat switch, ambil hanya pasangan itu dan regenerasi sesi; abaikan atau tolak parameter ID dari klien.
2. Terapkan perlindungan akun/cabang reserved di service penulisan, bukan hanya menyembunyikan tombol UI. Pastikan bisnis nyata tidak dapat memakai endpoint switch.
3. Tampilkan banner pada halaman owner/admin dan pastikan navigasi, breadcrumb, serta aksi yang tersedia sesuai peran aktif.
4. Uji back/pageshow sesudah switch agar tampilan peran lama tidak memperlihatkan data cabang/tenant yang kini tidak boleh diakses.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Switch sah | Owner demo → admin → owner | Session ID berubah pada tiap switch; admin hanya Cabang Utama; owner dua cabang | `[x]` |
| Banner | Buka halaman panel demo dan bisnis nyata | `MODE DEMO` beserta aksi tepat hanya pada demo | `[x]` |
| ID palsu | POST user/branch/tenant ID lain | Ditolak; tidak ada perubahan autentikasi atau data yang terbuka | `[x]` |
| Sesi usang | Akun/cabang/tenant reserved berubah atau demo expired | Request ditolak, sesi tidak memberi akses lama | `[x]` |
| Aset reserved | Owner mencoba menonaktifkan/pindah akun reserved atau Cabang Utama | Ditolak server; CRUD akun/cabang tambahan tetap bisa | `[x]` |
| Bisnis nyata | Owner non-demo POST `/demo/role` | Ditolak, peran dan sesi tetap | `[x]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=DemoFlowTest`
2. `rtk proxy docker compose exec -T app php artisan test --filter=LifecycleTest`
3. `rtk proxy docker build --target frontend -t ceklaundry-frontend -f docker/php/Dockerfile .`
4. `rtk proxy docker run --rm ceklaundry-frontend npm run typecheck`
5. `rtk proxy docker run --rm ceklaundry-frontend npm run build`

Expected: isolasi tenant/cabang dan regenerasi sesi terbukti; catat hasil browser saat verifikasi M6.

## 7. Out of Scope

1. Menambah role baru atau impersonation untuk tenant nyata.
2. Pembersihan permanen data demo (TICKET-047).

## 8. Completion Checklist

- [x] Otorisasi implementasi diterima; status `DONE` setelah verifikasi lokal/CI.
- [x] Switch, banner, dan perlindungan reserved diterapkan di server/UI.
- [x] Matriks penerimaan dan regresi otorisasi lulus.
- [x] Tidak ada akses lintas tenant/cabang melalui sesi atau ID klien.

Bukti aktual dan batas pengujian perangkat ada di [audit M6](../../audits/m6-verification.md). Status DONE mencakup implementasi dan gate lokal/CI; pemasangan serta splash Android/iOS fisik tetap belum diverifikasi.

# Implementation Plan: TICKET-006 (Panel developer, provisioning bisnis, dan lifecycle)

**Ticket:** `TICKET-006`  
**Status:** `READY`  
**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-005`  
**Tahap:** Urutan 1 — M1, pekerjaan 7

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [User stories](../initiate-file/user-stories.md) US-102/103/104; [PRD](../initiate-file/prd.md) 7.10/9.3 |
| Keterlacakan | US-102 AC1–4, US-103 AC1–4, US-104 AC1–8; AND-01, AND-21, ISO-03, ISO-06, SEC-01, SEC-07, SEC-08, LOK-03 |
| Provisioning | Tepat satu business+owner+business_settings+loyalty_settings dalam satu commit; active_until wajib bisnis nyata |
| Lifecycle | Evaluator TICKET-004; AKTIF sampai akhir active_until, tenggang+1..+7, baca-saja+8 WIB; warning mulai−7 |
| Hak developer | Administrasi dan jumlah cabang/transaksi30×24jam; tidak ada akses individual atau drill-down |
| Otorisasi | Pengguna menyetujui dan meminta implementasi seluruh TICKET-001–010 pada 28 September 2026. |

## 2. Objective

Developer dapat mendaftarkan bisnis dan mengelola masa aktif tanpa membuka data operasional pelanggan. Owner/admin menerima status akses terbaru dan banner yang sesuai, termasuk pada sesi yang sudah login.

## 3. Non-Negotiable Technical Contract

1. `app/Services/TenantProvisioner.php`: root baru lalu owner+settings atomik, email unik global, password awal wajib ganti. Loyalti default false/N10/null/null; business_settings mengikuti seluruh default skema.
2. `app/Services/LifecycleService.php`: perluas evaluator dengan mutator active_until/is_active di business root lock dan audit atomik. Reaktivasi/perpanjangan dari read-only menutup pending lama sebelum membuka akses kembali.
3. `app/Services/DeveloperBusinessSummary.php`: hanya nama/status/masa aktif/jumlah cabang/jumlah transaksi30hari dan identifier administrasi bisnis; tidak mengembalikan ID/baris transaksi/customer/payment.
4. `app/Http/Controllers/Developer/BusinessController.php`, `app/Http/Requests/Developer/`, `resources/js/Pages/Developer/Businesses/`, `routes/web.php`: daftar/provision/perpanjang/nonaktif-aktif/reset owner melalui AccountService; tidak ada delete tenant normal.
5. `app/Services/PendingNotificationInvalidator.php`, `app/Console/Commands/ExpireBusinessAccess.php`, `routes/console.php`: invalidasi log belum diotorisasi sesuai arsitektur2, scanner tiap menit untuk expiry; tidak menyentuh delivery marker in-flight. Tabel log sudah ada di TICKET-003, pengiriman tetap M3.
6. `resources/js/Components/BusinessLifecycleBanner.tsx`, `app/Http/Middleware/HandleInertiaRequests.php`: warning owner, tenggang mencolok, tombol tulis disabled di read-only; server tetap otoritas423.
7. `tests/Feature/Developer/BusinessManagementTest.php`, `tests/Integration/LifecycleTest.php`: data transaksi/log fixtures lengkap, tanpa endpoint membuat transaksi baru.

## 4. Scope of Changes

### A. Administrasi bisnis

1. Form bisnis/owner/masa aktif, validasi Indonesia, daftar dan angka agregat persis PRD7.10.
2. Reset owner memakai password sementara input developer, audit aman dan pencabutan sesi/token.
3. Tolak role nondeveloper pada route developer; data operational tidak terbuka lewat error, audit, job atau API.

### B. Lifecycle

1. Tanggal/jam keputusan dibaca setelah lock; perubahan berlaku pada request berikutnya tanpa menunggu cron.
2. Terapkan exception keamanan akun dan predikat GET resi tenant nyata pada evaluator; route resi nyata dan email publik tetap M2/M3.
3. Uji invalidasi pending dengan fixtures pada off→on cepat dan read-only→perpanjang sebelum worker; marker existing tetap mengikuti kontrak in-flight.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Provision sah | Bisnis, owner unik, tanggal aktif | Empat row domain utama terbentuk atomik; owner wajib ganti | `[ ]` |
| Gagal parsial | Email duplikat/fault sesudah owner insert | Seluruh provisioning rollback, tidak ada orphan | `[ ]` |
| Batas kalender | −7, hari0 23.59.59, +1, +7 23.59.59, +8 00.00 WIB | Warning/aktif/tenggang/read-only tepat | `[ ]` |
| Read-only | Owner/admin baca dan direct POST bisnis | Baca boleh, tulis423; ganti/reset/logout tetap bekerja | `[ ]` |
| Sesi lama | Developer menonaktifkan bisnis | Login/sesi panel tenant ditolak; developer tetap boleh mengelola administrasinya | `[ ]` |
| Agregat batas | Transaksi tepat now−30×24jam, batal, cabang nonaktif | Semua yang memenuhi rentang inklusif dihitung; tanpa data individual | `[ ]` |
| Tidak drill-down | Developer mencoba detail transaksi/audit operasi | Ditolak tanpa bocoran DTO/ID/credential | `[ ]` |
| Pending lama | Off→on/perpanjang sebelum job diproses | Pending lama tidak hidup kembali; audit dan perubahan atomik | `[ ]` |
| Prioritas | is_active=false, demo tepat expiry | Nonaktif prioritas; demo expired ditolak, bukan baca-saja | `[ ]` |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=BusinessManagementTest
rtk proxy docker compose exec -T app php artisan test --filter=LifecycleTest
rtk proxy docker compose exec -T app php artisan schedule:list
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

Target setelah implementasi; gunakan frozen clock untuk batas kalender, fault injection MySQL untuk provisioning/audit/invalidation. Jalankan lint/typecheck/build dari TICKET-002 setelah membangun ulang image frontend dan uji browser tiap role. US-104 AC4–6/8 yang menyentuh fitur masa depan diverifikasi pada evaluator/log fixture sekarang dan diulang pada endpoint/job nyata M2/M3; belum menjadi bukti E2E fitur tersebut.

## 7. Out of Scope

1. Konfigurasi provider/SMTP bisnis dan dispatch notifikasi M3.
2. Halaman resi publik M2, demo M6, billing otomatis atau self-signup bisnis.
3. Membuka data operasional individual kepada developer untuk troubleshooting.

## 8. Completion Checklist

- [ ] Lingkup diotorisasi dan dependensi selesai.
- [ ] Provisioning, agregat, lifecycle dan audit teruji termasuk failure/race.
- [ ] Seluruh matriks lulus pada area M1; integrasi milestone berikutnya tercatat eksplisit.
- [ ] UI Indonesia dan batas privasi diverifikasi di browser/API.
- [ ] Dokumentasi fitur, sesi dan status diperbarui sesuai bukti.

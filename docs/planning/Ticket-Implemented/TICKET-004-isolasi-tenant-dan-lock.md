# Implementation Plan: TICKET-004 (Isolasi tenant, policy cabang, dan protokol lock)

**Ticket:** `TICKET-004`
**Status:** `DONE`
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-003`
**Tahap:** Urutan 1 — M1, pekerjaan 6 dan guard dasar

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Arsitektur](../../initiate-file/architecture.md) bagian 2–4; [PRD](../../initiate-file/prd.md) 3.2 dan 9.3 |
| Keterlacakan | US-109 AC1–6, fondasi US-104/105/106; ISO-01–ISO-06, SEC-05, SEC-08, AND-01, AND-19 |
| Otoritas | Tenant dari user server, admin dari cabang terkini; konteks kosong fail closed |
| Respons | Resource tenant/cabang lain404; route salah peran403; business-write BACA_SAJA423 |
| Lock | READ COMMITTED, root business dahulu; satu unit satu bisnis, tanpa network call; retry deadlock/timeout seluruh unit maksimal3 |
| Otorisasi | Pengguna menyetujui dan meminta implementasi seluruh TICKET-001–010 pada 28 September 2026. |

## 2. Objective

Memberi seluruh service M1 dasar otorisasi dan transaksi yang sama sebelum route bisnis dibuka. Akses tidak boleh bergantung pada filter UI atau role/cabang yang tersimpan usang di sesi.

## 3. Non-Negotiable Technical Contract

1. `app/Tenancy/TenantContext.php`, `app/Models/Concerns/BelongsToBusiness.php`: scope model dengan business_id menurut daftar arsitektur; tanpa konteks menolak akses. User/AuditLog filter eksplisit, Business root; child tanpa business_id melalui parent terscope.
2. `app/Http/Middleware/ResolveTenant.php`, `app/Http/Middleware/EnsureBusinessAccess.php`, `bootstrap/app.php`: auth identity terkini, akun/cabang aktif, lifecycle; middleware bisa diuji dengan actingAs sebelum UI auth tersedia.
3. `app/Services/LifecycleService.php`: evaluator tunggal prioritas NONAKTIF/demo expiry/tanggal WIB sesuai PRD9.3. Pisahkan hak baca, tulis bisnis, keamanan akun, dan GET publik; mutator lifecycle ditambahkan TICKET-006.
4. `app/Services/BusinessTransaction.php`: wrapper transaction root-lock; baca ulang actor/assignment/lifecycle setelah lock. Mode keamanan akun tidak mensyaratkan writable; operasi developer global memakai row user lock tanpa tenant.
5. `app/Policies/BranchPolicy.php`, `app/Policies/UserPolicy.php`, `app/Policies/MasterServicePolicy.php`, `app/Policies/ServicePolicy.php`, `app/Policies/BusinessPolicy.php`: owner satu bisnis, admin satu cabang, developer administrasi terbatas. Tidak memakai superuser bypass umum.
6. `app/Jobs/Middleware/UseTenantContext.php`: validasi identitas tenant/parent dari payload server; clear context dalam finally, termasuk exception. Buat test job internal tanpa endpoint debug.
7. `tests/Feature/Tenancy/TenantIsolationTest.php`, `tests/Integration/BusinessLockTest.php`: dua tenant/multi-cabang, job berurutan, rollback dan proses MySQL paralel nyata.

## 4. Scope of Changes

### A. Akses server

1. Terapkan scope/binding/policy pada resource M1; abaikan business_id/role/branch admin dari payload sebagai sumber otoritas.
2. Batasi bypass pada operasi eksplisit yang disebut spesifikasi. Agregat developer belum membuka DTO/ID operasional.
3. Siapkan jalur context untuk CLI/job dan parent lookup terverifikasi; tidak memperluas akses developer ke data individual.

### B. Transaksi dan lifecycle dasar

1. Uji root lock dan validasi ulang actor; helper tidak commit sendiri. Customer→transaction→notification log dikunci ID menaik bila unit memerlukannya.
2. Buktikan dua tenant dapat bekerja independen, sedangkan mutasi satu tenant terserialisasi.
3. Buat evaluator lifecycle yang dipakai auth TICKET-005 dan UI/mutator TICKET-006; hindari ketergantungan melingkar.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Normal | Owner A, dua cabang A | Mengakses resource M1 kedua cabang A | `[x]` |
| Tenant lain | URL/query/body menunjuk resource B | 404/daftar kosong, tanpa bocoran isi/keberadaan | `[x]` |
| Cabang lain | Admin A1 meminta service A2 | 404/daftar kosong; route khusus owner403 | `[x]` |
| Developer | Meminta model/DTO operasi lewat jalur administratif | Ditolak; hanya akses administratif eksplisit | `[x]` |
| Konteks kosong/gagal | Job A exception lalu job B, atau job tanpa tenant | A dibersihkan, B terisolasi, kosong fail closed | `[x]` |
| Batas lifecycle | −7,0,+7,+8 hari WIB; demo tepat expiry | Evaluator sesuai PRD; BACA_SAJA tetap mengizinkan keamanan akun | `[x]` |
| Race otorisasi | Actor dipindah/nonaktif sebelum root lock diperoleh | Mutasi membaca keadaan terbaru dan ditolak bila tak berhak | `[x]` |
| Kegagalan write | Fault sesudah child pertama | Seluruh unit rollback; retry maksimal3, tanpa partial commit | `[x]` |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=TenantIsolationTest
rtk proxy docker compose exec -T app php artisan test --filter=BusinessLockTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

Perintah adalah target setelah implementasi. BusinessLockTest memakai dua proses/koneksi MySQL 8.4 dengan barrier dan DB uji khusus, tanpa enclosing test transaction tunggal. US-109 pada route transaksi/laporan/export nyata diuji ulang saat fitur M2–M5 tersedia; test fondasi bukan bukti route yang belum ada.

## 7. Out of Scope

1. Form login, CRUD panel, statistik, dan email autentikasi.
2. Implementasi endpoint transaksi/customer/laporan demi menguji scope.
3. Seluruh skenario concurrency pembayaran/merge/ledger/notifikasi milestone berikutnya.

## 8. Completion Checklist

- [x] Lingkup diotorisasi dan dependensi selesai.
- [x] Scope, policy, context, evaluator dan lock mengikuti satu kontrak.
- [x] Matriks negatif/isolation/fault dan MySQL paralel lulus.
- [x] Batas verifikasi dan pengujian ulang milestone berikutnya tercatat.
- [x] Dokumentasi fitur aktual, sesi dan status diperbarui.

## Hasil implementasi dan verifikasi

Selesai pada 28 September 2026 sesuai otorisasi pengguna. Bukti rinci, matriks per AC dan batas integrasi M2–M6 ada di [audit M1](../../audits/m1-verification.md); perintah aktual di [development](../../development.md).

CI final: [36350950049](https://github.com/cleveradit/ceklaundry/actions/runs/36350950049), 52 tes/306 assertions, quality/build/MySQL/browser lulus. Status checklist berlaku untuk lingkup M1; consumer masa depan tidak diklaim lulus E2E. Kasus kegagalan diuji melalui fault/guard dan setup bersih.

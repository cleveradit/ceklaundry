# Implementation Plan: TICKET-007 (Pengelolaan cabang dan akun admin)

**Ticket:** `TICKET-007`  
**Status:** `READY`  
**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-006`  
**Tahap:** Urutan 1 — M1, pekerjaan 8 bagian cabang/admin

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [User stories](../initiate-file/user-stories.md) US-105/106; [PRD](../initiate-file/prd.md) 7.9 dan 9.3 |
| Keterlacakan | US-105 AC1–5, US-106 AC1–5, US-109 AC1/2/4; AND-01, AND-19, DAT-01, ISO-01, ISO-02, ISO-06, SEC-01, SEC-05, SEC-07, SEC-08, LOK-04, UX-01, UX-02, UX-04 |
| Cabang | Owner mengelola satu bisnis; nonaktif ditolak bila ada DITERIMA/DIPROSES/SIAP_DIAMBIL; riwayat tetap ada |
| Admin | Tepat satu cabang per akun; satu cabang boleh banyak admin; reset oleh owner wajib ganti dan mencabut sesi/token |
| Otorisasi | Pengguna menyetujui dan meminta implementasi seluruh TICKET-001–010 pada 28 September 2026. |

## 2. Objective

Owner dapat mengelola identitas cabang dan menempatkan operator dengan aman. Perubahan penugasan atau penonaktifan segera membatasi sesi lama, sementara riwayat tetap utuh.

## 3. Non-Negotiable Technical Contract

1. `app/Services/BranchService.php`: create/update/activate/deactivate di root lock, validasi transaksi aktif di database terkini, tanpa hard delete. Normalisasi nomor sesuai PRD7.9, bukan hanya menghapus angka0 pertama.
2. `app/Services/AccountService.php`: tambah/update/pindah/nonaktif admin oleh owner, branch harus bisnis yang sama, validasi ulang actor sesudah lock. Field role/business_id tidak dapat dimutasi dari payload.
3. `app/Http/Controllers/Owner/BranchController.php`, `app/Http/Controllers/Owner/AdminController.php`, `app/Http/Requests/Owner/`, `resources/js/Pages/Owner/Branches/`, `resources/js/Pages/Owner/Admins/`, `routes/web.php`: CRUD yang diizinkan, konfirmasi tindakan berisiko, pesan Indonesia; reuse policy TICKET-004.
4. `app/Services/AccountService.php`: email trim/lowercase maksimal150 unik global, password sesuai SEC-01; email berubah mencabut sesi dan token alamat lama/baru. Reset operator audit atomik tanpa password.
5. `tests/Feature/Owner/BranchAdminTest.php`, `tests/Integration/BranchAdminConcurrencyTest.php`: guard nonaktif diuji dengan transaksi fixture sah dan writer MySQL dua proses memakai protokol root lock; bukan endpoint create transaksi M2.

## 4. Scope of Changes

### A. Cabang

1. Tambah/edit nama/alamat/telepon, daftar status, aktif/nonaktif dan aktifkan kembali.
2. Identitas master cabang terkini dapat dibaca consumer resi/status kelak; harga snapshot tidak disentuh.

### B. Admin

1. Tambah beberapa admin di cabang yang sama, edit identitas, pindah cabang, nonaktif/aktif, reset password sementara.
2. Admin tidak mendapat UI/route pengelolaan milik owner; ID tenant/cabang lain ditolak server.
3. Gunakan akun/cabang terbaru di request berikutnya, bukan memercayai assignment saat login.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Cabang baru | Nomor08/+62/62 dengan spasi/tanda hubung | Normalisasi62…; identitas tersimpan dan dapat diedit | `[ ]` |
| Nomor batas/gagal | Prefix62 diikuti8–13 digit; digit pertama0/terlalu panjang | Domain valid diterima; yang di luar batas ditolak tanpa pemotongan | `[ ]` |
| Cabang sibuk | Salah satu dari tiga status transaksi aktif | Penonaktifan ditolak dengan pesan jelas | `[ ]` |
| Hanya histori | Semua transaksi diambil/batal | Boleh nonaktif; histori tetap ada; aktifkan kembali berhasil | `[ ]` |
| Banyak admin | Dua email unik pada cabang sama | Keduanya valid, masing-masing tepat satu cabang dan wajib ganti password | `[ ]` |
| Pindah cabang | Admin A1 dipindah A2 dengan sesi lama | A1 tidak terbuka lagi; A2 sesuai policy terbaru | `[ ]` |
| Pemalsuan | OwnerA kirim branchB atau ubah role/business_id admin | Ditolak; tidak naik hak atau pindah tenant | `[ ]` |
| Reset/email/nonaktif | Owner reset atau ubah email/nonaktif admin | Sesi/token dicabut sesuai kontrak; audit reset tanpa rahasia; nonaktif tetap tidak login | `[ ]` |
| Race | Penonaktifan cabang vs writer transaksi fixture | Root lock memberi urutan sah; tidak ada cabang nonaktif bertransaksi aktif | `[ ]` |
| Read-only | POST owner pada bisnis BACA_SAJA | Tulis bisnis423, tidak mengubah cabang/admin | `[ ]` |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=BranchAdminTest
rtk proxy docker compose exec -T app php artisan test --filter=BranchAdminConcurrencyTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

Target sesudah implementasi. Uji concurrency di MySQL8.4 dua proses/barrier tanpa transaksi pembungkus tunggal; ulangi terhadap TransactionService/PaymentService nyata pada M2. Bangun ulang image frontend, jalankan lint/typecheck/build TICKET-002 dan uji tampilan HP: teks16px, target sentuh44px, pesan tanpa stack trace. Resi/status/laporan US-105 AC1/3/4 diuji lagi ketika M2/M5 tersedia.

## 7. Out of Scope

1. Histori/report UI, pembuatan transaksi/pembayaran dan halaman resi publik.
2. Hard delete cabang/admin atau perubahan histori actor ketika admin dipindah.
3. Peran baru, satu admin banyak cabang, dan pengelolaan bisnis lain.

## 8. Completion Checklist

- [ ] Lingkup diotorisasi dan dependensi selesai.
- [ ] CRUD yang diizinkan, revocation dan isolasi teruji.
- [ ] Guard cabang dan race MySQL lulus; uji ulang service M2 tercatat.
- [ ] UI owner diverifikasi di HP dan dokumentasi fitur aktual diperbarui.
- [ ] Sesi dan status diperbarui, tidak ada perubahan di luar lingkup.

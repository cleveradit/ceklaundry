# Implementation Plan: TICKET-009 (Pratinjau dan sinkronisasi layanan master)

**Ticket:** `TICKET-009`  
**Status:** `READY`  
**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-008`  
**Tahap:** Urutan 1 — M1, pekerjaan 8 bagian sinkronisasi

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [User stories](../initiate-file/user-stories.md) US-108; [PRD](../initiate-file/prd.md) 7.9; [arsitektur](../initiate-file/architecture.md) 4/5 |
| Keterlacakan | US-108 AC1–9 (edit langsung AC9 pada TICKET-008), US-109 AC1/4; AND-01, AND-08, AND-19, SEC-05, SEC-07, ISO-06, UX-04 |
| Pencocokan | Nama normalized dengan collation katalog; hanya master aktif dan cabang aktif yang dipilih |
| Efek | Nama sama timpa harga/durasi/minimum/satuan dan aktifkan lokal; nama baru tambah; lokal lain tetap |
| Konfirmasi | Preview perubahan/penambahan/reaktivasi sebelum simpan; fingerprint berubah409 dan konfirmasi baru |
| Atomisitas | Semua cabang pilihan+audit satu unit DB dengan root lock; tidak ada simpan parsial |
| Otorisasi | Pengguna menyetujui dan meminta implementasi seluruh TICKET-001–010 pada 28 September 2026. |

## 2. Objective

Owner dapat menyebarkan katalog dengan mengetahui dampaknya sebelum menyimpan. Perubahan data setelah pratinjau tidak boleh tertimpa tanpa konfirmasi ulang.

## 3. Non-Negotiable Technical Contract

1. `app/Services/MasterSyncService.php`: preview dari data server, fingerprint kanonis mencakup sumber master, target/cabang, atribut dan keadaan aktif yang memengaruhi hasil. Simpan menghitung ulang setelah root lock; input client bukan otoritas hitungan.
2. `app/Services/MasterSyncService.php`: semua cabang terpilih harus milik owner dan aktif. Audit aman satu unit DB; exception di salah satu write membatalkan seluruh perubahan.
3. `app/Http/Controllers/Owner/MasterSyncController.php`, `app/Http/Requests/Owner/PreviewMasterSyncRequest.php`, `app/Http/Requests/Owner/ApplyMasterSyncRequest.php`, `routes/web.php`: action preview dan apply terpisah; POST apply CSRF+fingerprint+konfirmasi eksplisit, tanpa menahan transaction selama pengguna membaca preview.
4. `resources/js/Components/MasterSyncPreview.tsx`, `resources/js/Pages/Owner/MasterServices/`, `resources/js/Pages/Owner/BranchServices/`: jalur Salin/Perbarui dari Master dan Sebarkan ke Cabang memakai service yang sama; preview per cabang dan total.
5. `tests/Feature/Owner/MasterSyncTest.php`, `tests/Integration/MasterSyncConcurrencyTest.php`: rename, stale, rollback antar-cabang, audit dan snapshot diuji dengan MySQL.

## 4. Scope of Changes

### A. Pratinjau

1. Tampilkan layanan ditambah/diperbarui/diaktifkan kembali, layanan lokal yang tetap, dan cabang tujuan.
2. Nama master baru setelah rename menambah layanan nama baru; nama lokal lama tetap.

### B. Simpan atomik

1. Validasi ulang actor/lifecycle/cabang/master, hitung fingerprint dan tolak stale dengan409.
2. Update seluruh atribut menurut aturan, pertahankan transaksi/item snapshot; tulis audit dalam commit yang sama.
3. Input cabang kosong/duplikat/asing/nonaktif divalidasi secara deterministik; jangan diam-diam melewatkan target yang tidak sah.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Cabang kosong | Dua master aktif, satu nonaktif | Preview2 tambah; hanya dua aktif disalin setelah konfirmasi | `[ ]` |
| Timpa/pertahankan |5 nama sama harga berbeda,2 baru,1 lokal khusus | Preview5 perbarui+2 tambah; lokal khusus tetap | `[ ]` |
| Reaktivasi | Lokal nama sama nonaktif, master aktif | Preview reaktivasi; aktif kembali dan semua atribut sesuai master | `[ ]` |
| Rename | Master A diubah B, lokal A existing | B ditambah, A tetap; tidak rename otomatis | `[ ]` |
| Stale | Harga/master/cabang/lokal berubah setelah preview | Apply409; tidak ada write/audit sukses; perlu preview dan konfirmasi baru | `[ ]` |
| Multi-cabang gagal | Fault pada write cabang kedua atau audit | Seluruh cabang rollback bersama | `[ ]` |
| Snapshot | Transaksi lama memakai harga sebelum sync | Item/snapshot lama tetap persis | `[ ]` |
| Akses/batas | Pilihan kosong, cabang nonaktif/asing, admin, read-only | Validasi/policy menolak sesuai kontrak; tidak ada partial apply | `[ ]` |
| Dua apply paralel | Dua preview sama, apply bersamaan | Root lock serial; request kedua stale bila state berubah, tidak menggandakan layanan | `[ ]` |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=MasterSyncTest
rtk proxy docker compose exec -T app php artisan test --filter=MasterSyncConcurrencyTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

Target setelah implementasi; concurrency memakai dua proses/koneksi MySQL8.4 dan barrier, fault injection setiap write/audit. Jalankan lint/typecheck/build TICKET-002 pada image frontend terbaru, lalu uji dua tab browser dan dua cabang. Race sync melawan quote transaksi nyata diuji ulang M2; fixture snapshot tidak membuktikan PricingService yang belum ada.

## 7. Out of Scope

1. Sync otomatis saat master berubah atau sinkronisasi tanpa konfirmasi.
2. Memutasi snapshot transaksi, menghitung ulang tagihan atau membuat quote transaksi M2.
3. Menyalin master nonaktif, memodifikasi lokal khusus, atau mengaktifkan cabang nonaktif.

## 8. Completion Checklist

- [ ] Lingkup diotorisasi dan dependensi selesai.
- [ ] Preview, apply, audit dan semua AC US-108 lingkup M1 terverifikasi.
- [ ] Stale409, isolasi, rollback multi-cabang dan concurrency lulus.
- [ ] Browser/quality checks lulus; batas integrasi M2 tercatat.
- [ ] Dokumentasi fitur, sesi dan status diperbarui.

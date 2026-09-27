# Implementation Plan: TICKET-008 (Katalog layanan master dan cabang)

**Ticket:** `TICKET-008`  
**Status:** `READY`  
**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-007`  
**Tahap:** Urutan 1 — M1, pekerjaan 8 bagian katalog

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [PRD](../initiate-file/prd.md) 7.8/7.9; [skema](../initiate-file/database-schema.md) 2.5/2.6 |
| Keterlacakan | US-107 AC1–4, US-108 AC9, US-109 AC1/2/4; AND-01, AND-08, AND-19, AND-22, ISO-06, SEC-05, UX-01, UX-02, UX-04, LOK-01, LOK-02 |
| Nama | Trim/collapse spasi; unik case-insensitive accent-sensitive per bisnis untuk master, per cabang untuk layanan lokal |
| Nilai | Satuan kg/item, harga rupiah bulat positif, durasi1–65.535jam; minimum null atau kg positif dalam domain skema, item wajib null |
| Histori | Ubah master tidak otomatis mengubah cabang; ubah lokal hanya cabangnya; snapshot transaksi tidak berubah |
| Otorisasi | Pengguna menyetujui dan meminta implementasi seluruh TICKET-001–010 pada 28 September 2026. |

## 2. Objective

Owner dapat menyiapkan katalog master dan layanan khusus cabang dengan validasi yang konsisten. Data historis tetap dapat dipercaya saat katalog diubah atau dinonaktifkan.

## 3. Non-Negotiable Technical Contract

1. `app/Services/ServiceCatalogService.php`: create/update/activate/deactivate master/lokal di business root lock, validasi ulang policy/branch/lifecycle; tidak hard delete.
2. `app/Support/ServiceName.php`, `app/Http/Requests/Owner/SaveMasterServiceRequest.php`, `app/Http/Requests/Owner/SaveBranchServiceRequest.php`: normalisasi nama tunggal untuk unique dan matching sync; validasi panjang/rentang tanpa truncation atau floating-point uang.
3. `app/Http/Controllers/Owner/MasterServiceController.php`, `app/Http/Controllers/Owner/BranchServiceController.php`, `resources/js/Pages/Owner/MasterServices/`, `resources/js/Pages/Owner/BranchServices/`, `routes/web.php`: owner mengelola master/lokal, admin tidak mendapat hak pengelolaan; service admin yang dibaca tetap scoped cabang.
4. `app/Services/ServiceCatalogService.php`: master yang dipakai hadiah aktif tidak boleh dinonaktifkan/diubah ke item sebelum program dimatikan/hadiah diganti. Uji menggunakan loyalty_settings fixture aktif; tidak membuat UI aktivasi M4.
5. `tests/Feature/Owner/ServiceCatalogTest.php`: nama/harga/batas durasi/minimum, isolasi, master→lokal dan snapshot transaksi fixture.

## 4. Scope of Changes

### A. Katalog

1. Daftar/tambah/edit/nonaktif-aktif master dan layanan cabang; express cukup nama/harga/durasi biasa.
2. Nominal Indonesia, feedback validasi dan satu aksi utama per halaman; menu owner tetap maksimal5–6 menu utama.

### B. Konsistensi

1. Jangan melakukan update massal cabang pada edit master; sinkronisasi eksplisit menjadi TICKET-009.
2. Simpan atribut master/lokal sesuai skema; relasi layanan/cabang harus satu bisnis meski payload direkayasa.
3. Baca ulang pengaturan hadiah setelah root lock saat mengubah master.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Normal | Master kg7000/durasi24/min3 dan item25000/durasi48/minnull | Tersimpan sesuai satuan; format tampilan Indonesia | `[ ]` |
| Nama duplikat | ` Cuci   Setrika ` dan `cuci setrika` pada bisnis sama | Nama dinormalisasi; duplikat ditolak | `[ ]` |
| Batas nilai | Harga1/4.294.967.295, durasi1/65.535 | Batas sah diterima sesuai tipe;0/overflow ditolak | `[ ]` |
| Minimum tidak sah | Minimum0/negatif, pecahan melebihi presisi, minimum pada item | Ditolak, tidak dipotong otomatis | `[ ]` |
| Isolasi | Nama sama bisnis berbeda; branch milik bisnis lain | Nama antarbisnis boleh; relasi silang ditolak | `[ ]` |
| Histori | Edit harga master/lokal dengan item snapshot existing | Lokal tidak berubah saat edit master; snapshot tetap sama | `[ ]` |
| Hadiah aktif | Nonaktifkan/ubah ke item master hadiah aktif | Ditolak; boleh setelah program nonaktif/hadiah diganti | `[ ]` |
| Akses | Admin kirim POST owner; owner read-only kirim POST | Salah peran403; business-write read-only423 | `[ ]` |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=ServiceCatalogTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

Target sesudah implementasi. Uji database MySQL untuk collation/unique/CHECK; bangun ulang image frontend dan jalankan lint/typecheck/build TICKET-002. Verifikasi form HP, kontras4,5:1, target44px, nominal dan pesan Indonesia. Perhitungan harga transaksi AND-22 diuji lengkap pada M2/M4; tiket ini membuktikan domain input katalog saja.

## 7. Out of Scope

1. Sinkronisasi massal/preview (TICKET-009).
2. PricingService transaksi, promo, perolehan/penukaran stempel, dan aktivasi loyalti.
3. Mengubah snapshot histori atau membuat tipe layanan express khusus.

## 8. Completion Checklist

- [ ] Lingkup diotorisasi dan dependensi selesai.
- [ ] Katalog, validasi batas/keunikan/isolasi dan guard hadiah teruji.
- [ ] Perubahan katalog terbukti tidak mengubah snapshot.
- [ ] UI dan pemeriksaan frontend lulus; feature docs/sesi diperbarui.
- [ ] DONE hanya sesudah verifikasi, tanpa perubahan di luar lingkup.

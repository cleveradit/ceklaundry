# Verifikasi fondasi M1

Tanggal verifikasi lokal: 28 September 2026. Lingkup hanya TICKET-001–010: persiapan dan M1. Spesifikasi sumber tidak diubah.

## Hasil dan lingkungan

- MySQL8.4 nyata, PHP8.4, database uji terpisah; seluruh hasil akhir suite dicatat pada penutupan di bawah.
- Docker build app/web/frontend dan lima layanan lokal berhasil healthy; migrasi berhasil/idempoten.
- TypeScript strict, ESLint dan Vite lulus; Pint dan validator spesifikasi menjadi gate CI.
- Browser Google Chrome154.0.8037.57 di Windows, Playwright1.63.0; desktop1440×1000 dan viewport HP390×844. Alur bootstrap developer, wajib ganti password, provisioning owner, cabang/admin, master/sync, konflik dua tab, logout/back dan salah peran lulus. Tidak ada pageerror JavaScript.
- Screenshot desktop/mobile ditinjau: tidak overflow horizontal, lima menu owner, teks/kontrol utama16px, tombol minimal44px. Teks utama #203b32 di putih dan tombol putih/#12634f mempunyai kontras >4,5:1. Ini simulasi viewport, bukan uji perangkat HP fisik.
- Pengiriman email diuji dengan transport mock/array, token sekali pakai, job stale/hold/demo/cutoff, exception aman dan tanpa retry. SMTP provider nyata belum dikonfigurasi/dikirimi email.

## Bukti concurrency dan kegagalan

`ConcurrentTestCase` menggunakan migrate:fresh database uji tanpa transaksi pembungkus test. Child PHP terpisah membaca actor, melewati barrier, lalu benar-benar menunggu row lock MySQL. Satu bisnis tertahan; bisnis lain berhasil. Setelah lock dilepas, actor nonaktif ditolak403 dan assignment admin terbaru menolak cabang lama404. Writer transaksi fixture lebih dulu membuat penonaktifan ditolak422. Dua apply preview yang sama menghasilkan satu commit dan satu409, tanpa duplikasi. Enam proses limiter hanya mengizinkan lima login pada fixed window sama.

Fault setelah owner, cabang sync kedua, audit, serta token/queue membatalkan unit atomik. Batas password11/12/72/73 byte, angka katalog, nomor telepon, generated-owner unique, FK lintas tenant, token tepat60 menit, lifecycle WIB H−7/0/+7/+8, dan transaksi30hari diuji.

Temuan browser yang diperbaiki: redirect form Inertia tanpa referer dan clearHistory yang semula hilang saat invalidate session. Regresi dilindungi tes backend serta browser nyata.

## Matriks tiap AC

Nama test berada di `tests/Feature/` atau `tests/Integration/`; runner browser di `tests/browser/m1.cjs`. “Lulus fondasi” tidak menyatakan endpoint consumer yang belum ada lulus E2E.

| AC | Bukti test | Status | Batas / pemilik uji berikutnya |
|---|---|---|---|
| US-101 AC1 | AuthenticationTest; AuthResetQueueTest; M1JourneyTest | Lulus M1 | — |
| US-101 AC2 | AuthenticationTest; AuthResetQueueTest; M1JourneyTest | Lulus M1 | — |
| US-101 AC3 | AuthenticationTest; AuthResetQueueTest; M1JourneyTest | Lulus M1 | — |
| US-101 AC4 | AuthenticationTest; AuthResetQueueTest; M1JourneyTest | Lulus M1 | — |
| US-101 AC5 | AuthenticationTest; AuthResetQueueTest; M1JourneyTest | Lulus M1 | — |
| US-101 AC6 | AuthenticationTest; AuthResetQueueTest; M1JourneyTest | Lulus M1 | — |
| US-101 AC7 | AuthenticationTest; AuthResetQueueTest; M1JourneyTest | Lulus M1 | — |
| US-101 AC8 | AuthenticationTest; AuthResetQueueTest; M1JourneyTest | Lulus fondasi; integrasi lanjutan belum tersedia | Demo memakai fixture; provisioning/sesi demo nyata M6. |
| US-101 AC9 | AuthenticationTest; AuthResetQueueTest; M1JourneyTest | Lulus fondasi; integrasi lanjutan belum tersedia | Rekonsiliasi auth teruji; semua transport operasional M3/M6. |
| US-102 AC1 | BusinessManagementTest; SchemaContractTest | Lulus M1 | — |
| US-102 AC2 | BusinessManagementTest; SchemaContractTest | Lulus M1 | — |
| US-102 AC3 | BusinessManagementTest; SchemaContractTest | Lulus M1 | — |
| US-102 AC4 | BusinessManagementTest; SchemaContractTest | Lulus M1 | — |
| US-103 AC1 | BusinessManagementTest; LifecycleTest; BranchAdminTest | Lulus fondasi; integrasi lanjutan belum tersedia | COUNT transaksi memakai fixture; alur create nyata M2. |
| US-103 AC2 | BusinessManagementTest; LifecycleTest; BranchAdminTest | Lulus M1 | — |
| US-103 AC3 | BusinessManagementTest; LifecycleTest; BranchAdminTest | Lulus M1 | — |
| US-103 AC4 | BusinessManagementTest; LifecycleTest; BranchAdminTest | Lulus fondasi; integrasi lanjutan belum tersedia | Batas statistik memakai fixture transaksi; ulangi dengan create M2. |
| US-104 AC1 | LifecycleTest; AuthenticationTest | Lulus M1 | — |
| US-104 AC2 | LifecycleTest; AuthenticationTest | Lulus M1 | — |
| US-104 AC3 | LifecycleTest; AuthenticationTest | Lulus M1 | — |
| US-104 AC4 | LifecycleTest; AuthenticationTest | Lulus fondasi; integrasi lanjutan belum tersedia | Invalidasi pending fixture teruji; scheduler notifikasi M3. |
| US-104 AC5 | LifecycleTest; AuthenticationTest | Lulus fondasi; integrasi lanjutan belum tersedia | Evaluator publicAllowed teruji; endpoint GET resi M2. |
| US-104 AC6 | LifecycleTest; AuthenticationTest | Lulus fondasi; integrasi lanjutan belum tersedia | Hak business-write teruji; email publik/POST M3. |
| US-104 AC7 | LifecycleTest; AuthenticationTest | Lulus M1 | — |
| US-104 AC8 | LifecycleTest; AuthenticationTest | Lulus fondasi; integrasi lanjutan belum tersedia | Business-write423 dan keamanan akun teruji; wa.me M2/email manual M3. |
| US-105 AC1 | BranchAdminTest; BranchAdminConcurrencyTest | Lulus fondasi; integrasi lanjutan belum tersedia | CRUD identitas teruji; pemakaian di resi/status M2. |
| US-105 AC2 | BranchAdminTest; BranchAdminConcurrencyTest | Lulus M1 | — |
| US-105 AC3 | BranchAdminTest; BranchAdminConcurrencyTest | Lulus fondasi; integrasi lanjutan belum tersedia | Histori fixture tetap ada; laporan M5. |
| US-105 AC4 | BranchAdminTest; BranchAdminConcurrencyTest | Lulus fondasi; integrasi lanjutan belum tersedia | Reaktivasi/identitas dan snapshot fixture teruji; create/resi M2. |
| US-105 AC5 | BranchAdminTest; BranchAdminConcurrencyTest | Lulus fondasi; integrasi lanjutan belum tersedia | Race writer fixture memakai root protocol; ulangi TransactionService M2. |
| US-106 AC1 | BranchAdminTest; BranchAdminConcurrencyTest; M1JourneyTest | Lulus M1 | — |
| US-106 AC2 | BranchAdminTest; BranchAdminConcurrencyTest; M1JourneyTest | Lulus M1 | — |
| US-106 AC3 | BranchAdminTest; BranchAdminConcurrencyTest; M1JourneyTest | Lulus M1 | — |
| US-106 AC4 | BranchAdminTest; BranchAdminConcurrencyTest; M1JourneyTest | Lulus M1 | — |
| US-106 AC5 | BranchAdminTest; BranchAdminConcurrencyTest; M1JourneyTest | Lulus M1 | — |
| US-107 AC1 | ServiceCatalogTest | Lulus M1 | — |
| US-107 AC2 | ServiceCatalogTest | Lulus M1 | — |
| US-107 AC3 | ServiceCatalogTest | Lulus fondasi; integrasi lanjutan belum tersedia | Snapshot fixture teruji; pricing/create nyata M2. |
| US-107 AC4 | ServiceCatalogTest | Lulus fondasi; integrasi lanjutan belum tersedia | Guard loyalty_settings teruji; UI/program hadiah M4. |
| US-108 AC1 | MasterSyncTest; MasterSyncConcurrencyTest; ServiceCatalogTest; browser/m1.cjs | Lulus M1 | — |
| US-108 AC2 | MasterSyncTest; MasterSyncConcurrencyTest; ServiceCatalogTest; browser/m1.cjs | Lulus M1 | — |
| US-108 AC3 | MasterSyncTest; MasterSyncConcurrencyTest; ServiceCatalogTest; browser/m1.cjs | Lulus M1 | — |
| US-108 AC4 | MasterSyncTest; MasterSyncConcurrencyTest; ServiceCatalogTest; browser/m1.cjs | Lulus M1 | — |
| US-108 AC5 | MasterSyncTest; MasterSyncConcurrencyTest; ServiceCatalogTest; browser/m1.cjs | Lulus M1 | — |
| US-108 AC6 | MasterSyncTest; MasterSyncConcurrencyTest; ServiceCatalogTest; browser/m1.cjs | Lulus fondasi; integrasi lanjutan belum tersedia | Snapshot fixture tidak berubah; transaksi nyata M2. |
| US-108 AC7 | MasterSyncTest; MasterSyncConcurrencyTest; ServiceCatalogTest; browser/m1.cjs | Lulus M1 | — |
| US-108 AC8 | MasterSyncTest; MasterSyncConcurrencyTest; ServiceCatalogTest; browser/m1.cjs | Lulus M1 | — |
| US-108 AC9 | MasterSyncTest; MasterSyncConcurrencyTest; ServiceCatalogTest; browser/m1.cjs | Lulus fondasi; integrasi lanjutan belum tersedia | Edit lokal/snapshot fixture teruji; pricing transaksi M2. |
| US-109 AC1 | TenantIsolationTest; SchemaContractTest; BusinessLockTest | Lulus fondasi; integrasi lanjutan belum tersedia | Resource M1 teruji; route transaksi/customer/settings baru M2–M5 wajib uji ulang. |
| US-109 AC2 | TenantIsolationTest; SchemaContractTest; BusinessLockTest | Lulus fondasi; integrasi lanjutan belum tersedia | Katalog cabang M1 teruji; transaksi/dashboard operasional M2. |
| US-109 AC3 | TenantIsolationTest; SchemaContractTest; BusinessLockTest | Lulus fondasi; integrasi lanjutan belum tersedia | Route/policy M1 dan DTO agregat teruji; semua route operasi M2–M5. |
| US-109 AC4 | TenantIsolationTest; SchemaContractTest; BusinessLockTest | Lulus fondasi; integrasi lanjutan belum tersedia | FK/model/schema M1 teruji; payment/status/ledger baru M2/M4. |
| US-109 AC5 | TenantIsolationTest; SchemaContractTest; BusinessLockTest | Lulus fondasi; integrasi lanjutan belum tersedia | Pembatasan peran/cabang fondasi teruji; customer search/ledger/report/export M2–M5. |
| US-109 AC6 | TenantIsolationTest; SchemaContractTest; BusinessLockTest | Lulus M1 | — |

## Penutupan gate

[GitHub Actions run 36350950049](https://github.com/cleveradit/ceklaundry/actions/runs/36350950049) **success**, kode `27c1d72`. Checkout bersih membangun image, menyalakan DB/runtime, migrasi, Pint103 file, **52 tes/306 assertions tanpa warning**, TypeScript strict, ESLint, Vite, fault gate type/lint, validator spesifikasi dan browser Chromium153.0.8010.12 semuanya lulus. Runner browser menyimpan empat screenshot sebagai artifact; credential fixture tidak diunggah.

Verifikasi lokal tambahan: rollback seluruh lima migrasi lalu migrate ulang pada ceklaundry_test berhasil; proyek Compose sekali pakai ceklaundry-clean-m1 di port8090 menjalankan worker probe dan cron heartbeat. Restart mempertahankan cache DB dan marker storage; migrate berikutnya no-op. Container/volume uji sekali pakai dibersihkan; volume development dipertahankan.

Run awal36349672071 gagal saat inisialisasi volume storage; gate menghentikan langkah selanjutnya. Perbaikan memakai volume nocopy serta mkdir runtime. Init MySQL diisolasi dalam subshell dan healthcheck memakai TCP agar tidak salah membaca server sementara saat bootstrap. [Run ulang36350064687](https://github.com/cleveradit/ceklaundry/actions/runs/36350064687) berhasil; final run di atas juga menghapus warning dotenv melalui placeholder .env kosong dan mengaktifkan --fail-on-warning.

Password owner bersarang tidak di-trim dan tidak masuk old input pada validation error; regression test BusinessManagementTest lulus. Whitespace Unicode nama katalog dinormalisasi sebelum uniqueness; edit lokal dan sinkronisasi tidak mengubah snapshot fixture.

Reset password admin oleh owner tetap tersedia di BACA_SAJA sebagai operasi keamanan, sementara CRUD bisnis tetap423; BranchAdminTest memverifikasi password/flag/audit dan penolakan business-write. POST login tanpa token CSRF pada runtime lokal menghasilkan419.

Seluruh TICKET-001–010 DONE dan berada di [arsip](../planning/Ticket-Implemented/index.md). Dokumentasi penutupan dapat mempunyai commit terpisah dari kode yang diverifikasi; tidak mengubah kode runtime.

## Handoff M2

Mulai tiket berikutnya dari skema payment/status history dan domain transaksi/pricing sesuai plan. Gunakan BusinessTransaction, scope/cabang terbaru, FK komposit, snapshot dan aturan idempotensi; jangan menambahkan bypass developer. Setelah TransactionService tersedia, ulangi guard cabang, statistik, snapshot katalog/sync dan lifecycle publik dengan transaksi asli. Halaman resi, WA, notifikasi pelanggan, laporan, demo dan PWA belum tersedia.

# Audit kesiapan Tahap 7 — verifikasi menyeluruh dan staging

Tanggal: 2 Oktober 2026. Basis kode: `badae8e72bcbe5d34c7af839b796036dc3a6441e` pada `main`. Audit ini memeriksa bukti yang tersedia untuk [Tahap 7](../plan.md#11-tahap-7--verifikasi-menyeluruh-dan-staging); **Tahap 7 belum selesai** dan belum ada klaim kesiapan produksi.

## Bukti yang diperiksa ulang

- [CI untuk commit yang sama](https://github.com/cleveradit/ceklaundry/actions/runs/36908571762) `success`: build, migrasi, Pint, MySQL, frontend, validator dokumen, browser M1–M6, dan kapasitas M5 seluruhnya lulus.
- Suite PHP/MySQL 8.4 lokal: `136 passed (1210 assertions)` dalam 722,74 detik. Ini mencakup proses MySQL terpisah untuk pembayaran, kuota WA, worker, scheduler, loyalti, transaksi, demo, dan snapshot laporan. File `SendNotification.php` dan `NotificationFlowTest.php` di image cocok hash-nya dengan checkout.
- Pint lulus 218 file; ESLint, TypeScript, Vite build, dan validator spesifikasi lulus. Validator mencatat 72 FR tertelusur, 51 user story, 257 AC, 73 NFR, dan nol error. Pemeriksaan teks audit menemukan setiap satu dari 257 AC memiliki baris di audit M1–M6; ini adalah **cakupan pencatatan**, bukan bukti bahwa semua AC telah lulus penuh.
- Browser QA lokal Chrome 154 pada viewport 390 × 844: perjalanan M2 dan M3 lulus pada `ceklaundry_test`. CI menjalankan browser M1–M6, termasuk viewport desktop untuk M1/M5 dan HP emulasi untuk M2–M4/M6. QA kemudian dihentikan dan file fixture berkredensial di host serta storage dihapus.
- Pengukuran tambahan halaman status publik pada QA lokal, 20 konteks Chrome baru, 4 Mbps turun/1 Mbps naik, RTT 150 ms, CPU 4×: P95 **933 ms**. Transfer halaman depan/status/cetak masing-masing **7.888/6.720/5.891 byte**, tanpa bundle React. Konteks/cache baru dipakai dalam satu proses browser; ini belum mengukur seluruh kondisi staging atau perangkat fisik yang disyaratkan KIN-01.
- Artefak CI commit ini menunjukkan dashboard P95 **256,9 ms** pada 200 bisnis, 50.000 transaksi, 99.994 pembayaran dan profil throttling yang sama; hasil tersebut adalah pengukuran runner CI, bukan kapasitas VPS staging.

## Penilaian tujuh pekerjaan Tahap 7

| Pekerjaan di plan | Hasil verifikasi | Status |
|---|---|---|
| 1. FR, story, AC, NFR | 72 FR dan 257 AC terlacak di spesifikasi/audit. Audit M3–M6 masih memiliki 36 baris bertanda `P` atau `S` (M3 15, M4 13, M5 2, M6 6); beberapa merupakan batas staging/perangkat, beberapa membutuhkan tes kasus khusus. Belum ada matriks bukti implementasi final untuk seluruh 73 NFR. | Terbuka |
| 2. Gate kualitas/CI | Semua gate lokal di atas dan CI commit `badae8e` lulus. | Lulus untuk kode ini |
| 3. MySQL paralel/fault | Suite proses terpisah lulus. Audit lama masih menandai beberapa interleaving/fault khusus sebagai bukti parsial, terutama AND-21, restore, dan beberapa kombinasi loyalti/merge. | Parsial |
| 4. Staging menyerupai produksi | Tidak ada konfigurasi staging atau GitHub Environment yang ditemukan di repo/CI; target dan akses staging belum diberikan untuk audit ini. | Belum dijalankan |
| 5. HP dan desktop seluruh alur | Browser CI M1–M6 lulus; HP adalah viewport emulasi. Provider email/WA nyata, printer thermal 58 mm, pemasangan/splash Android/iOS, Firefox/Safari, dan perjalanan pada perangkat fisik belum dibuktikan. Uji printer fisik M2 pernah dilewati atas instruksi pengguna. | Parsial |
| 6. Kinerja dan aksesibilitas | KIN-03 lolos di CI; pengukuran lokal tambahan mendukung KIN-01/02 dengan batas metode di atas. KIN-05 (admin menyelesaikan transaksi pelanggan lama dalam <1 menit) belum diukur. Audit aksesibilitas lintas halaman/kontras/target sentuh belum lengkap. Navigasi owner melanggar UX-01. | Parsial; ada ketidaksesuaian |
| 7. Deploy, restart, backup/restore, rollback | Command hold/cutoff dan rekonsiliasi diuji dengan fake/dry run, tetapi deploy staging, backup fisik, restart/cutover lintas instance, rollback, RPO ≤24 jam, dan RTO ≤4 jam belum diuji. | Belum dijalankan |

## Temuan yang perlu ditindaklanjuti

1. **UX-01 tidak terpenuhi pada navigasi owner.** `resources/js/Layouts/AppLayout.tsx` menampilkan 10 tautan utama (`Ringkasan` sampai `Operasional`) pada desktop dan HP, sedangkan UX-01 membatasi 5–6 menu utama. Screenshot `m5-owner-mobile.png` dari CI menunjukkan semuanya. Pernyataan audit M1 tentang lima menu owner perlu dikoreksi setelah rancangan navigasi diputuskan.
2. **AND-21 belum memiliki bukti tes eksplisit untuk semua perubahan saat job tertunda.** Kode `PendingNotificationInvalidator`, `CancellationService`, `TransactionStateMachine`, `LifecycleService`, `NotificationSettingsService`, dan preflight `SendNotification` menangani kondisi itu. Tes yang ada membuktikan off→on pengingat dan lifecycle, tetapi tidak secara langsung menguji satu job tertunda lalu pickup, batal, dan saklar WA off sebagai matriks regresi. Audit M3 menandai US-301.4/US-307.2 parsial.
3. **Kesenjangan bukti lokal lintas milestone** tercatat di audit: M4 perlu kasus khusus kompensasi ulang, batas hadiah, stale N/promo, dan interleaving penukaran dengan writer lain; M6 perlu deploy dua versi service worker, ukuran waktu reload, serta perangkat Android/iOS. Prioritaskan kasus yang mengubah risiko rilis; baris bertanda `P` bukan otomatis bug implementasi.
4. **Gate operasional staging belum ada.** Diperlukan target staging, konfigurasi rahasia di luar repo, tujuan notifikasi uji yang dikendalikan, perangkat, latihan backup/restore dengan jam RPO/RTO, dan bukti rollback sebelum kriteria selesai Tahap 7 dicentang.

## Keputusan kesiapan

Kriteria selesai Tahap 7 pada `plan.md` tetap **belum terpenuhi**. Tidak ditemukan kegagalan pada suite lokal atau CI untuk kode saat ini, tetapi keberhasilan itu tidak menggantikan bukti staging, perangkat, restore, dan perbaikan UX-01. Pekerjaan lanjutan perlu dipecah menjadi tiket verifikasi/regresi, perbaikan navigasi dan aksesibilitas, staging integrasi/perangkat, serta latihan pemulihan dan rollback. Jangan menandai Tahap 7 `DONE` sampai bukti setiap kriteria dapat ditinjau.

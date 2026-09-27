# Panduan Pengembangan CekLaundry

Disusun: 28 September 2026.

Dokumen ini adalah panduan pribadi untuk mengikuti pembangunan CekLaundry dari spesifikasi sampai siap digunakan. Setiap tahap menjelaskan tujuan, urutan pekerjaan, hasil yang perlu diperiksa, dan syarat untuk melanjutkan. Dokumen ini tidak otomatis mengotorisasi implementasi seluruh tahap dan tidak menggantikan ticket implementasi.

## 1. Titik awal proyek

Saat panduan ini dibuat, repository berisi spesifikasi dan dokumentasi. Belum ada aplikasi Laravel, frontend, migrasi database, pengujian aplikasi, atau konfigurasi Docker Compose. Belum ada ticket implementasi aktif.

Validator dokumentasi telah lulus dengan 72 kebutuhan fungsional, 51 user story, 257 kriteria penerimaan, dan 73 kebutuhan nonfungsional. Hasil tersebut adalah pemeriksaan konsistensi dokumen; perilaku aplikasi baru dapat dibuktikan setelah kode dan pengujiannya tersedia.

CekLaundry akan melayani banyak bisnis laundry dalam satu aplikasi. Developer mengelola administrasi bisnis, owner mengelola bisnis beserta cabangnya, admin menangani operasional satu cabang, dan pelanggan mengecek status melalui kode resi tanpa login.

### Stack yang sudah ditetapkan

| Bagian | Target proyek |
|---|---|
| Backend | PHP 8.4 dan Laravel 12 |
| Database | MySQL 8.4, InnoDB, `utf8mb4` |
| Panel pengguna | Inertia.js, React, TypeScript strict, Tailwind CSS, shadcn/ui, Vite; tanpa SSR |
| Halaman publik | Blade, CSS ringan, JavaScript seperlunya |
| Queue, session, cache | Database; tidak memerlukan Redis |
| Scheduler | Cron Laravel setiap menit |
| Deployment | Docker Compose di VPS, reverse proxy HTTPS, GitHub Actions |

Versi dependensi yang belum ditetapkan dipilih dan dikunci saat scaffold, berdasarkan kompatibilitas dengan stack tersebut.

Pemeriksaan lingkungan pada 27 September 2026 menemukan Docker Engine dan Compose dapat diakses, Composer tersedia, PHP lokal versi 8.5.4, serta `node` dan `npm` tidak ditemukan pada shell WSL yang diperiksa. Ini hanya catatan awal, bukan kondisi yang dianggap selalu berlaku. Periksa kembali saat mulai. Container dapat menyediakan PHP 8.4 dan alat build frontend tanpa bergantung pada versi PHP host.

## 2. Cara memakai panduan ini

1. Pilih tahap pertama yang belum selesai dan pastikan tahap sebelumnya telah memenuhi kriteria selesai.
2. Baca sumber kebutuhan untuk tahap tersebut. Ringkasan di sini membantu navigasi; detail perilaku tetap mengikuti spesifikasi.
3. Pecah pekerjaan menjadi ticket kecil yang dapat diimplementasikan dan diuji. Satu milestone biasanya membutuhkan beberapa ticket.
4. Pada setiap ticket, tetapkan referensi user story dan NFR, lingkup, dependensi, kasus normal, kasus batas, kasus gagal, serta perintah verifikasi.
5. Jalankan implementasi sesuai lingkup yang diminta. Permintaan implementasi yang jelas merupakan otorisasi untuk lingkup tersebut; keputusan penting yang masih ambigu perlu diselesaikan sebelum bagian terkait dikerjakan.
6. Periksa hasil secara langsung dan baca bukti pengujian sebelum menandai selesai. Tampilan yang dapat dibuka belum membuktikan aturan bisnis benar.
7. Catat hasil dan langkah berikutnya dalam alur ticket proyek. Jika ada kegagalan yang menghalangi tahap berikutnya, selesaikan dahulu.

Gunakan [template ticket](planning/_template-implementation-plan.md) dan ikuti [workflow proyek](ai-context.md). Status ticket berjalan dari `DRAFT` atau `REVIEW` bila masih ada keputusan terbuka, menjadi `READY` ketika lingkup telah diotorisasi, kemudian `DONE` setelah implementasi dan verifikasi. Periksa indeks serta arsip sebelum menentukan nomor ticket.

Contoh permintaan untuk memulai satu pekerjaan:

> Kerjakan fondasi lingkungan pengembangan sebagai bagian M1. Buat ticket sesuai workflow, siapkan scaffold dan layanan lokal sesuai spesifikasi, lalu verifikasi dari database kosong. Laporkan hasil, perintah menjalankan aplikasi, dan pekerjaan yang belum termasuk lingkup ticket ini.

Untuk pekerjaan berikutnya, sebutkan bagian yang ingin dikerjakan dan hasil yang ingin diperiksa. Hindari menganggap seluruh milestone selesai hanya karena satu ticket selesai.

## 3. Peta perjalanan

| Urutan | Tahap | Hasil utama |
|---|---|---|
| 0 | Persiapan | Ticket pertama, lingkungan dan lingkup yang jelas |
| 1 | M1 — Fondasi dan tenant | Aplikasi dapat dijalankan; akun, bisnis, cabang, dan layanan terisolasi |
| 2 | M2 — Operasional inti | Alur cucian masuk sampai diambil dapat dicoba lengkap |
| 3 | M3 — Notifikasi | Pengiriman email, pengingat, dan WA opsional dapat dipantau |
| 4 | M4 — Loyalti dan promo | Stempel dan promo terintegrasi dengan transaksi |
| 5 | M5 — Laporan owner | Pendapatan, tagihan, dashboard, dan ekspor dapat dipercaya |
| 6 | M6 — Demo dan PWA | Prospek dapat mencoba demo; aplikasi dapat di-install |
| 7 | Verifikasi menyeluruh dan staging | Seluruh alur teruji pada lingkungan menyerupai produksi |
| 8 | Produksi dan pemeliharaan | Aplikasi tersedia melalui HTTPS dan operasional terpantau |

Tahap 0, 7, dan 8 adalah pengelompokan pekerjaan persiapan dan rilis, bukan milestone produk tambahan. Pengujian, keamanan, CI, dan dokumentasi dilakukan sejak M1, kemudian diverifikasi bersama pada tahap rilis.

**Tiga titik pencapaian yang perlu dibedakan:** selesai fondasi M1 berarti aplikasi dapat dijalankan; selesai M2 berarti alur operasional inti dapat dicoba; seluruh M1–M6 beserta verifikasi rilis selesai berarti lingkup produk lengkap telah dipenuhi. Semua M1–M6 tetap wajib dikerjakan.

## 4. Tahap 0 — Persiapan

**Tujuan:** memulai pembangunan dengan lingkungan dan pekerjaan pertama yang jelas.

### Urutan pekerjaan

1. Baca [konteks proyek](ai-context.md), [sesi terakhir](planning/current-session.md), [indeks ticket](planning/index.md), dan [audit spesifikasi](audits/final-system-audit.md).
2. Periksa status repository agar pekerjaan yang sudah ada tidak tertimpa.
3. Periksa Docker, Compose, akses WSL, port layanan, serta kebutuhan PHP/Composer dan Node/package manager di host atau container.
4. Buat ticket fondasi M1 dengan hasil yang dapat diuji: scaffold, runtime, database, frontend, konfigurasi, dan CI dasar. Pecah autentikasi serta fitur tenant menjadi ticket lanjutan bila diperlukan.
5. Tetapkan cara menyimpan konfigurasi lokal dan rahasia. Repository hanya menyimpan contoh environment tanpa kredensial asli.

### Kriteria selesai

- [ ] Ticket pertama memiliki lingkup, dependensi, dan kriteria penerimaan yang jelas.
- [ ] Jalur menjalankan runtime dan build frontend telah dipilih sesuai target proyek.
- [ ] Tidak ada keputusan teknis yang menghalangi pekerjaan pertama.

## 5. M1 — Fondasi dan tenant

**Tujuan:** aplikasi dapat dijalankan dan setiap pengguna hanya dapat mengakses bisnis serta cabang yang menjadi haknya.

**Prasyarat:** tahap persiapan selesai.

**Rujukan:** [US-101 sampai US-109](initiate-file/user-stories.md#epic-m1--fondasi--tenant), [arsitektur](initiate-file/architecture.md), [skema database](initiate-file/database-schema.md), serta NFR keamanan, isolasi, integritas, dan pemeliharaan.

### Urutan pekerjaan

1. Buat scaffold Laravel, panel Inertia/React/TypeScript, dan kerangka Blade publik. Siapkan format kode, lint, pemeriksaan tipe, serta kerangka pengujian.
2. Buat Docker Compose untuk `app`, `web`, `db`, `worker`, dan `cron`. Siapkan build frontend, penyimpanan persisten, konfigurasi timezone, serta koneksi database untuk queue/session/cache.
3. Buat migrasi fondasi sesuai kontrak skema: bisnis, pengaturan, akun, cabang, layanan, dan tabel infrastruktur yang dibutuhkan. Buat pengaturan loyalti awal nonaktif sesuai spesifikasi; fitur loyalti dikembangkan pada M4.
4. Siapkan cara membuat akun developer awal tanpa password contoh di repository.
5. Implementasikan login, logout, wajib ganti password awal, reset password, pencabutan sesi, serta pembatasan akses berdasarkan peran. Jalur email keamanan akun menjadi bagian autentikasi M1; notifikasi transaksi dikembangkan pada M3.
6. Implementasikan konteks tenant, policy cabang, validasi relasi, dan pola transaksi database beserta urutan lock yang menjadi dasar service berikutnya.
7. Implementasikan panel developer untuk mendaftarkan bisnis dan owner, mengelola masa aktif, serta menonaktifkan bisnis. Terapkan peringatan, tenggang, dan mode baca-saja.
8. Implementasikan pengelolaan cabang, admin, layanan master, layanan cabang, dan pratinjau sinkronisasi layanan.
9. Aktifkan CI dasar dan dokumentasikan perintah nyata untuk setup, migrasi, data awal, menjalankan layanan, build, lint, dan tes.

### Kriteria selesai

- [ ] Setup dari checkout dan database kosong dapat diulang dengan petunjuk yang tersedia.
- [ ] Developer dapat membuat bisnis beserta owner; owner dapat mengelola cabang, admin, dan layanan.
- [ ] Owner hanya melihat bisnisnya; admin dibatasi cabangnya; manipulasi ID tidak membuka data pihak lain.
- [ ] Developer tidak mendapatkan akses ke data operasional individual melalui hak administratifnya.
- [ ] Pergantian password, penonaktifan akun/cabang, dan lifecycle bisnis menegakkan akses sesuai spesifikasi.
- [ ] Sinkronisasi layanan bersifat atomik dan sesuai pratinjau.
- [ ] Pengujian fondasi dan pemeriksaan kualitas lulus di CI.

**Yang diperiksa sendiri:** buat dua bisnis dengan beberapa cabang dan akun. Coba berpindah peran serta membuka resource bisnis/cabang lain. Pastikan aplikasi benar-benar membatasi akses di server.

## 6. M2 — Operasional inti

**Tujuan:** owner/admin dapat mencatat cucian hingga diserahkan, dan pelanggan dapat mengecek statusnya.

**Prasyarat:** tenant, akun, cabang, layanan, serta aturan akses M1 telah teruji.

**Rujukan:** [US-201 sampai US-215](initiate-file/user-stories.md#epic-m2--operasional-inti), aturan bisnis PRD, serta NFR pembayaran, concurrency, privasi publik, dan cetak.

### Urutan pekerjaan

1. Implementasikan pelanggan: pencarian, normalisasi nomor HP, tambah/edit identitas, keunikan per bisnis, dan penggabungan sesuai hak cabang.
2. Implementasikan kalkulasi harga, minimum berat, layanan satuan, estimasi selesai, dan snapshot harga. Gunakan kalkulasi server sebagai otoritas.
3. Implementasikan pembuatan transaksi atomik, kode resi unik global, item, riwayat awal, dan pencegahan transaksi ganda saat request diulang.
4. Implementasikan pembayaran penuh, DP, cicilan, sisa tagihan, dan saklar DP. Pembayaran yang sudah tercatat tidak dapat diedit atau dihapus. DP yang sudah berjalan tetap dapat dilanjutkan ketika saklar dimatikan.
5. Implementasikan status `DITERIMA → DIPROSES → SIAP_DIAMBIL → SUDAH_DIAMBIL`, pembatalan yang diizinkan, aturan kunci transaksi, serta audit. Cucian hanya dapat diambil setelah lunas.
6. Buat dashboard admin, pencarian transaksi, daftar terlambat/menumpuk, serta akses operasional owner lintas cabang miliknya.
7. Buat halaman depan pencarian resi dan `/t/{kode_resi}` dengan data pribadi disamarkan. Terapkan pembatasan request dan pemisahan data publik dari data panel.
8. Buat resi cetak thermal 58 mm, QR, dan tautan WhatsApp manual. Promo/stempel yang belum diimplementasikan tetap nonaktif; integrasinya ditambahkan pada M4.

### Kriteria selesai

- [ ] Alur pelanggan baru → transaksi → pembayaran → proses → siap → lunas → diambil dapat dijalankan.
- [ ] Perhitungan harga, pembulatan, minimum berat, dan estimasi sesuai contoh serta batas di spesifikasi.
- [ ] Double-click/retry tidak menggandakan transaksi atau pembayaran; pembayaran berlebih ditolak.
- [ ] Pengujian MySQL paralel membuktikan pembayaran dan perubahan status tidak menghasilkan keadaan tidak sah.
- [ ] Transaksi terkunci hanya dapat dipulihkan melalui mekanisme pembatalan yang diizinkan.
- [ ] Cek resi bekerja tanpa login dan fungsi intinya tidak bergantung pada JavaScript.
- [ ] Resi tercetak terbaca, QR dapat dipindai, dan alur admin dapat digunakan dari HP.

**Yang diperiksa sendiri:** buat transaksi Rp74.500, bayar DP Rp30.000, lalu lunasi Rp44.500. Pastikan sisa tagihan benar dan pengambilan ditolak sebelum lunas. Coba pula request berulang, pembatalan, serta akses resi publik.

## 7. M3 — Notifikasi

**Tujuan:** pelanggan dapat menerima pemberitahuan dan pengingat tanpa mengganggu transaksi utama.

**Prasyarat:** status transaksi, data pelanggan, pembayaran, dan lifecycle bisnis sudah stabil.

**Rujukan:** [US-301 sampai US-308](initiate-file/user-stories.md#epic-m3--notifikasi), arsitektur bagian notifikasi dan restore, serta NFR AND-04, AND-10, AND-11, AND-20, AND-21, AND-25, AND-27, dan AND-28.

### Urutan pekerjaan

1. Implementasikan konfigurasi SMTP global/per bisnis dan pengaturan perilaku notifikasi. Rahasia hanya dapat diganti oleh pihak berwenang dan tidak dikirim kembali ke browser.
2. Implementasikan pendaftaran serta verifikasi email notifikasi per transaksi sesuai status yang diizinkan.
3. Implementasikan log, identitas kiriman unik, reservasi job atomik bersama perubahan database, worker, dan pemulihan pekerjaan yang tertunda.
4. Tambahkan email siap-diambil, pengingat terjadwal, kiriman manual, dan pencatatan pembukaan tautan `wa.me`.
5. Implementasikan adapter WhatsApp sesuai kontrak provider, saklar per peristiwa, dan batas bulanan. WA otomatis default nonaktif; alur aplikasi tetap berfungsi dengan email dan tautan manual.
6. Implementasikan penanganan recipient ketika nomor pelanggan berubah atau pelanggan digabung. Bedakan kiriman yang belum pernah dicoba dari kiriman yang sudah pernah dicoba.
7. Terapkan penghentian notifikasi yang tidak relevan setelah pickup, pembatalan, perubahan lifecycle, atau saklar dimatikan.
8. Implementasikan outbound hold dan cutoff setelah restore, pemantauan queue/scheduler, serta penanganan hasil pengiriman yang tidak pasti.

### Kriteria selesai

- [ ] Email, pengingat, konfigurasi, kuota, dan log mengikuti seluruh acceptance criteria.
- [ ] Kegagalan penyedia tidak membatalkan transaksi laundry yang sah.
- [ ] Timeout/hasil tidak pasti masuk pemeriksaan dan tidak dikirim ulang otomatis secara buta.
- [ ] Worker ganda, crash, job hilang, serta scheduler berhenti/pulih telah diuji.
- [ ] Perubahan nomor/merge tidak mengotorisasi kiriman baru ke recipient lama secara keliru.
- [ ] Restore hold memblokir seluruh transport outbound, termasuk email keamanan akun, sampai prosedur pemulihan selesai.
- [ ] Kredensial dan data pelanggan tidak bocor ke log teknis atau panel pihak yang tidak berhak.

**Yang diperiksa sendiri:** pindahkan transaksi ke siap-diambil, lihat hasil log, lalu simulasikan penyedia gagal dan timeout. Pastikan label status tidak menyatakan berhasil saat hasil belum diketahui.

## 8. M4 — Loyalti dan promo

**Tujuan:** stempel dan diskon berjalan konsisten dengan transaksi serta pembayaran.

**Prasyarat:** aturan harga, pembayaran, pembatalan, dan concurrency M2 teruji; milestone sebelumnya selesai.

**Rujukan:** [US-401 sampai US-407](initiate-file/user-stories.md#epic-m4--loyalti--promo), aturan loyalti/promo PRD, dan NFR integritas ledger.

### Urutan pekerjaan

1. Implementasikan pengaturan program stempel dan layanan hadiah beserta batas berat.
2. Implementasikan ledger stempel, perolehan pertama saat lunas yang memenuhi syarat, penukaran, dan kompensasi pembatalan. Saldo berasal dari penjumlahan ledger.
3. Implementasikan promo milik owner, cakupan cabang, masa berlaku, dan validasinya di server.
4. Integrasikan penukaran dan satu promo dengan kalkulasi harga, snapshot, transaksi, resi, halaman status, serta isi notifikasi yang relevan.
5. Uji penukaran bersamaan, perubahan pengaturan, pembatalan, penggabungan pelanggan, dan transaksi lama. Jangan memberi stempel retroaktif pada transaksi yang tidak memenuhi kontrak.

### Kriteria selesai

- [ ] Saldo yang sama tidak dapat dipakai dua kali melalui request bersamaan.
- [ ] Hadiah, minimum berat, promo, dan pembulatan menghasilkan nominal sesuai spesifikasi.
- [ ] Pembatalan memberi kompensasi ledger sekali saja tanpa menghapus riwayat.
- [ ] Perubahan layanan atau promo tidak mengubah snapshot transaksi lama.
- [ ] Saldo negatif yang sah akibat kompensasi ditampilkan beserta penjelasan, bukan disamarkan menjadi nol.

**Yang diperiksa sendiri:** kumpulkan stempel, tukarkan hadiah pada transaksi yang juga memakai promo, lalu uji pembatalan yang memengaruhi saldo. Cocokkan nominal dan riwayatnya.

## 9. M5 — Laporan owner

**Tujuan:** owner dapat memantau aktivitas, uang yang diterima, dan tagihan dengan angka yang konsisten.

**Prasyarat:** transaksi, pembayaran, pembatalan, promo, dan loyalti telah terintegrasi.

**Rujukan:** [US-501 sampai US-506](initiate-file/user-stories.md#epic-m5--laporan-owner), PRD bagian pelaporan, dan NFR AND-24.

### Urutan pekerjaan

1. Implementasikan riwayat dan filter cabang, tanggal, status transaksi, serta status bayar.
2. Implementasikan pendapatan berdasarkan tanggal pembayaran, bukan tanggal pembuatan transaksi. Pembayaran transaksi batal dikeluarkan sesuai kontrak laporan.
3. Implementasikan daftar sisa tagihan, dashboard owner, ringkasan harian, dan grafik.
4. Implementasikan ekspor CSV dengan pembatasan akses dan perlindungan formula injection.
5. Cocokkan laporan dengan data pembayaran serta snapshot baca yang konsisten. Pastikan join beberapa payment tidak menggandakan berat atau item.

### Kriteria selesai

- [ ] DP bulan Juli dan pelunasan bulan Agustus masuk ke periode pembayaran masing-masing.
- [ ] Pembatalan kemudian hari memengaruhi laporan sesuai definisi yang ditetapkan.
- [ ] Batas hari WIB, filter cabang, dashboard, grafik, dan CSV menghasilkan angka yang selaras.
- [ ] Owner tidak dapat melihat laporan bisnis lain; admin tidak mendapat akses laporan khusus owner.

**Yang diperiksa sendiri:** buat data pembayaran lintas tanggal/bulan, cocokkan angka manual, lalu bandingkan tabel, grafik, dan CSV.

## 10. M6 — Demo dan PWA

**Tujuan:** calon owner dapat mencoba aplikasi dengan aman dan pengguna dapat memasangnya ke home screen.

**Prasyarat:** seluruh alur M1–M5 tersedia agar demo mewakili produk.

**Rujukan:** [US-601 sampai US-606](initiate-file/user-stories.md#epic-m6--mode-demo--pwa), NFR demo, retensi data, kompatibilitas, dan AND-26.

### Urutan pekerjaan

1. Implementasikan tombol coba demo, pembuatan tenant terisolasi, dan data contoh sesuai spesifikasi.
2. Implementasikan banner demo dan perpindahan role owner/admin yang dibatasi pada sesi demo tersebut.
3. Pastikan seluruh jalur outbound demo ditekan, termasuk jalur keamanan akun dan verifikasi. Simulasi tidak mengirim notifikasi nyata.
4. Terapkan batas pembuatan demo, expiry tujuh hari, penolakan akses ketika expired, dan pembersihan terjadwal seluruh data terkait sesuai urutan foreign key.
5. Buat manifest, ikon, service worker, halaman offline, dan mekanisme pembaruan aset.
6. Cache hanya aset statis yang diizinkan. Data dinamis tidak disimpan untuk penggunaan offline dan input offline tidak direplay.

### Kriteria selesai

- [ ] Dua pengunjung demo mendapat tenant terpisah tanpa dapat membaca data satu sama lain.
- [ ] Demo tidak mengirim email/WA nyata melalui jalur mana pun.
- [ ] Demo expired langsung tidak dapat diakses, termasuk bila purge tertunda.
- [ ] Pembersihan memulihkan ketertinggalan setelah scheduler kembali aktif dan aman dijalankan bersamaan.
- [ ] PWA teruji pada perangkat yang ditargetkan; logout/pergantian akun tidak menampilkan data tenant sebelumnya dari cache.
- [ ] Pembaruan aplikasi tidak menghilangkan form yang belum disimpan tanpa pemberitahuan.

**Yang diperiksa sendiri:** coba dua demo, uji pergantian peran, install aplikasi, matikan jaringan, lalu logout/login sebagai bisnis lain. Pastikan data lama tidak muncul kembali.

## 11. Tahap 7 — Verifikasi menyeluruh dan staging

**Tujuan:** membuktikan seluruh bagian bekerja bersama sebelum digunakan untuk operasional nyata.

### Urutan pekerjaan

1. Cocokkan seluruh FR, user story, acceptance criteria, dan NFR dengan implementasi serta bukti uji. Selesaikan kekurangan yang tersisa.
2. Jalankan lint PHP/frontend, pemeriksaan TypeScript, unit/feature tests, integrasi MySQL 8.4, dan build produksi melalui CI.
3. Jalankan uji paralel dengan koneksi/proses MySQL nyata dan fault injection. SQLite atau satu transaksi pengujian yang membungkus semua proses tidak cukup untuk membuktikan concurrency.
4. Jalankan aplikasi pada staging dengan susunan layanan menyerupai produksi. Gunakan data uji dan tujuan notifikasi yang dikendalikan.
5. Uji seluruh alur dari HP dan desktop: autentikasi, transaksi, pembayaran, cetak, resi publik, notifikasi, loyalti, laporan, dan demo.
6. Periksa performa dan aksesibilitas terhadap target NFR, termasuk keterbacaan panel owner dan pengalaman cetak thermal.
7. Latih deploy, migrasi, restart worker, pemulihan layanan, backup/restore, serta prosedur rollback yang mempertimbangkan kompatibilitas skema dan data.

### Kriteria selesai

- [ ] Semua kebutuhan M1–M6 memiliki implementasi dan verifikasi yang sesuai.
- [ ] Tidak ada kegagalan isolasi data, integritas pembayaran, atau pengiriman yang masih menghalangi rilis.
- [ ] CI dan pemeriksaan staging lulus dengan bukti hasil yang dapat ditinjau.
- [ ] Uji restore memenuhi target RPO maksimal 24 jam dan RTO maksimal 4 jam.
- [ ] Petunjuk setup, operasional, dan pemulihan sesuai perintah yang benar-benar tersedia.

## 12. Tahap 8 — Produksi dan pemeliharaan

**Tujuan:** aplikasi dapat diakses pengguna dan tetap dapat dioperasikan ketika terjadi gangguan.

### Urutan pekerjaan

1. Siapkan VPS, domain, DNS, reverse proxy, sertifikat HTTPS, dan konfigurasi lingkungan produksi. Tentukan kapasitas berdasarkan kebutuhan serta hasil pengujian.
2. Simpan `APP_KEY`, akses database, dan konfigurasi SMTP/WA secara aman. WA otomatis hanya diaktifkan untuk bisnis yang memerlukannya dan sudah memiliki konfigurasi lengkap.
3. Deploy image/build yang telah lolos CI. Jalankan migrasi dan bootstrap akun awal secara terkendali; jangan memasukkan data uji ke tenant nyata.
4. Pastikan worker, cron, session, cache, storage, dan restart layanan berjalan sesuai konfigurasi.
5. Aktifkan backup database terenkripsi harian di lokasi terpisah dengan retensi minimal tujuh hari. Simpan backup `APP_KEY` secara aman dan terpisah.
6. Aktifkan pemantauan kegagalan database/backup, heartbeat scheduler, dan pekerjaan queue terlambat. Log teknis harus disanitasi.
7. Lakukan pemeriksaan setelah deploy, onboarding bisnis awal, serta pemantauan transaksi dan notifikasi pertama.
8. Jadwalkan pemeliharaan dependensi, pemeriksaan kapasitas, evaluasi kegagalan, dan latihan restore berkala.

### Kriteria selesai

- [ ] Aplikasi dapat diakses melalui HTTPS dan alur penting lolos pemeriksaan setelah deploy.
- [ ] Bisnis nyata memiliki akun, cabang, layanan, dan konfigurasi yang benar.
- [ ] Worker, scheduler, pemantauan, serta backup berfungsi dan dapat diperiksa hasilnya.
- [ ] Prosedur pemulihan pernah dicoba, termasuk hold/cutoff outbound agar pesan historis tidak otomatis dikirim ulang.
- [ ] Pengelola mengetahui cara menangani queue gagal, gangguan penyedia, dan pemulihan database.

Backup database tidak menjamin deduplikasi kiriman eksternal yang terjadi setelah titik backup. Prosedur restore harus mengikuti rekonsiliasi, hold, dan cutoff pada spesifikasi; jangan menganggap tidak adanya log sebagai bukti pesan belum terkirim.

## 13. Batas yang harus dijaga selama pengembangan

- Data bisnis dan cabang selalu dibatasi di server, termasuk pada search, ekspor, job, dan laporan.
- Pembayaran dan riwayat audit mengikuti aturan tambah-saja; tidak ada fitur edit paksa atau buka-kunci transaksi.
- Developer mengelola administrasi tenant tanpa akses khusus ke transaksi atau identitas pelanggan individual.
- Seluruh UI/pesan menggunakan bahasa Indonesia, nominal rupiah bulat, nomor HP tersimpan dalam format `62…`, dan waktu Asia/Jakarta.
- Jangan menambah payment gateway, self-signup bisnis, akun pelanggan, antar-jemput, pengeluaran/laba-rugi, aplikasi native, atau offline write. Daftar lengkap berada pada PRD bagian di luar cakupan.
- Tidak ada jaminan siap produksi hanya karena halaman berhasil dibuka, build berhasil, atau validator dokumen lulus.

## 14. Sumber rujukan

Jika ada perbedaan dengan ringkasan panduan ini, gunakan hierarki spesifikasi proyek dan selesaikan ketidaksesuaian sebelum mengimplementasikan bagian terkait.

| Dokumen | Dipakai untuk |
|---|---|
| [PRD](initiate-file/prd.md) | Cakupan produk, aturan bisnis, milestone, dan hal di luar cakupan |
| [User stories](initiate-file/user-stories.md) | Perilaku yang harus diterima dan matriks keterlacakan kebutuhan |
| [Arsitektur](initiate-file/architecture.md) | Kontrak service, tenant, concurrency, notifikasi, dan operasi |
| [Skema database](initiate-file/database-schema.md) | Tabel, kolom, relasi, constraint, dan retensi |
| [NFR](initiate-file/nfr.md) | Keamanan, kualitas, pengujian, performa, dan pemulihan |
| [Audit final](audits/final-system-audit.md) | Koreksi spesifikasi dan skenario kritis yang harus dipertahankan |
| [Konteks proyek](ai-context.md) | Mandat implementasi dan workflow dokumentasi |
| [Indeks ticket](planning/index.md) | Urutan pekerjaan implementasi yang sedang aktif |
| [Sesi terakhir](planning/current-session.md) | Posisi pekerjaan terakhir dan langkah berikutnya |

Validator spesifikasi dapat dijalankan dari root repository dengan `python3 docs/audits/validate-final-specs.py`. Perintah menjalankan aplikasi, migrasi, frontend, dan pengujian runtime harus mengikuti dokumentasi yang dibuat setelah scaffold tersedia.

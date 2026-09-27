# PRD — CekLaundry: Aplikasi Monitoring Status Laundry

| | |
|---|---|
| **Nama produk (sementara)** | CekLaundry |
| **Tanggal** | 8 Juli 2026 |
| **Platform** | Aplikasi web (responsif, mobile-first, PWA ringan) |

> **Cara membaca dokumen ini (untuk agentic AI):** Dokumen ini adalah *source of truth* dan mencatat **keseluruhan** kebutuhan aplikasi. Semua kebutuhan fungsional diberi ID (contoh: `FR-A02`) dan label **milestone (M1–M6)**. Milestone hanyalah **urutan pengerjaan** — seluruh kebutuhan di semua milestone wajib diselesaikan, tidak ada yang boleh dilewati. Kerjakan berurutan dari M1. Fitur yang sengaja tidak dibangun tercantum eksplisit di Bagian **14. Di Luar Cakupan** — jangan membangun apa pun dari daftar itu. Jika ada hal yang tidak dijelaskan di dokumen ini, ikuti Bagian **15. Keputusan yang Sudah Ditetapkan**; jika tetap ambigu, tanyakan kepada pemilik produk — jangan mengarang kebutuhan baru.

---

## 1. Ringkasan Produk

Aplikasi web untuk memonitor status cucian di bisnis laundry, mirip pengalaman "cek resi" pada aplikasi pengiriman paket. Pelanggan mengecek status cuciannya secara mandiri melalui kode resi atau QR code **tanpa login**. Admin laundry mencatat transaksi masuk, memperbarui status, mencatat pembayaran (termasuk uang muka/DP), dan mencetak resi. Pemilik laundry (owner) mengelola cabang, layanan & harga, promo & program stempel, akun admin, serta memantau pendapatan.

Aplikasi bersifat **multi-tenant**: satu aplikasi melayani **banyak bisnis laundry sekaligus**, dan data antarbisnis terisolasi total. **Developer** (pembuat aplikasi) memegang peran tertinggi: mendaftarkan bisnis laundry baru beserta akun owner-nya, mengatur masa aktif tiap bisnis, dan memegang seluruh konfigurasi teknis (WhatsApp API & email per bisnis). Untuk pemasaran, tersedia tombol **"Coba Demo"** yang otomatis membuat lingkungan demo terisolasi bagi calon pemilik laundry.

**Masalah yang diselesaikan:**

1. Pelanggan tidak tahu cuciannya sudah selesai atau belum, sehingga sering menelepon atau datang sia-sia.
2. Cucian yang sudah selesai menumpuk di laundry karena pelanggan tidak diingatkan untuk mengambil.
3. Pemilik tidak punya catatan rapi tentang transaksi, pembayaran, dan pendapatan — apalagi jika punya lebih dari satu cabang.
4. Developer membutuhkan cara mudah mendemonstrasikan aplikasi kepada calon pemilik laundry tanpa persiapan manual.

**Aksi terpenting (critical user journey):** hal pertama dan terpenting yang dilakukan pelanggan saat membuka aplikasi adalah **mengecek status laundry-nya**. Halaman depan aplikasi harus langsung menampilkan kolom input kode resi — tanpa halaman perantara, tanpa login.

---

## 2. Tujuan & Metrik Keberhasilan

| Tujuan | Metrik |
|---|---|
| Pelanggan mandiri mengecek status | Mayoritas pengecekan status dilakukan lewat aplikasi, bukan telepon/datang langsung |
| Cucian selesai cepat diambil | Jumlah cucian berstatus "Siap Diambil" lebih dari 3 hari menurun |
| Input transaksi cepat | Admin dapat mencatat 1 transaksi baru dalam waktu kurang dari 1 menit |
| Keuangan tercatat | 100% pembayaran (termasuk DP) tercatat; pemilik bisa melihat total pendapatan per periode |
| Owner nyaman memakai aplikasi | Owner (termasuk yang tidak muda) dapat memahami dashboard & laporan tanpa dilatih |
| Biaya notifikasi terkendali | Aplikasi berfungsi penuh tanpa biaya WhatsApp; jika WA aktif, jumlah pesan terpantau & terbatasi |
| Aplikasi mudah dipasarkan | Calon pemilik laundry bisa mencoba aplikasi lengkap dalam 1 klik tanpa bantuan developer |

---

## 3. Pengguna & Peran

### 3.1 Persona

1. **Pelanggan** — masyarakat umum yang menitipkan cucian. Tidak punya akun dan tidak perlu login. Mengakses aplikasi lewat HP, biasanya dari link/QR di resi. Kemampuan teknologinya beragam, jadi tampilan harus sangat sederhana.
2. **Admin Laundry** — pegawai di satu cabang. Login lewat HP atau komputer kasir. Tugas: mencatat cucian masuk, memperbarui status, mencetak resi, mencatat pembayaran/DP, menerapkan promo & penukaran stempel, mengingatkan pelanggan.
3. **Pemilik Laundry (Owner)** — pemilik satu bisnis laundry yang bisa memiliki **lebih dari satu cabang**. Umumnya bukan orang muda dan bukan orang teknis — antarmuka untuknya harus bagus, besar, dan tidak membingungkan (lihat Bagian 12.2). Mengelola admin, layanan master & layanan cabang, promo, program stempel, pengaturan perilaku notifikasi, dan laporan. **Owner juga dapat melakukan seluruh fungsi operasional admin di semua cabang miliknya** (untuk owner yang turun tangan sendiri).
4. **Developer** — pembuat sekaligus pemasar aplikasi. Mendaftarkan bisnis laundry baru beserta akun owner-nya, mengatur masa aktif tiap bisnis (aktivasi manual; pembayaran langganan terjadi di luar aplikasi), dan memegang seluruh **konfigurasi teknis** per bisnis (WhatsApp API, email pengirim) agar owner tidak pernah berurusan dengan hal teknis.
5. **Calon Pemilik Laundry (pengguna demo)** — prospek yang mencoba aplikasi lewat tombol "Coba Demo" tanpa mendaftar.

### 3.2 Matriks Hak Akses

| Kemampuan | Pelanggan | Admin | Owner | Developer |
|---|:---:|:---:|:---:|:---:|
| Cek status via kode resi/QR (tanpa login) | ✅ | ✅ | ✅ | ✅ |
| Mendaftarkan email untuk notifikasi | ✅ | — | — | — |
| Login | — | ✅ | ✅ | ✅ |
| Membuat & mengelola transaksi | — | ✅ (cabangnya saja) | ✅ (semua cabang bisnisnya) | — |
| Update status cucian & mencatat pembayaran/DP | — | ✅ (cabangnya saja) | ✅ | — |
| Cetak/kirim resi | — | ✅ | ✅ | — |
| Kelola data pelanggan (cari/tambah/edit/gabung) | — | ✅ | ✅ | — |
| Menerapkan promo & penukaran stempel pada transaksi | — | ✅ | ✅ | — |
| Kelola layanan master & layanan cabang | — | — | ✅ | — |
| Kelola promo & pengaturan program stempel | — | — | ✅ | — |
| Kelola cabang (nama, alamat, telp) | — | — | ✅ | — |
| Kelola akun admin (termasuk reset password admin) | — | — | ✅ | — |
| Laporan transaksi & pendapatan | — | — | ✅ | — |
| Pengaturan perilaku notifikasi & saklar DP | — | — | ✅ | — |
| Kelola bisnis laundry (tenant) & akun owner | — | — | — | ✅ |
| Mengatur masa aktif bisnis | — | — | — | ✅ |
| Konfigurasi teknis per bisnis (WA API, email pengirim) | — | — | — | ✅ |

Aturan penting:

- **Isolasi tenant:** owner dan admin hanya dapat melihat/mengelola data milik bisnisnya sendiri. Tidak ada jalur apa pun (termasuk manipulasi URL/API) untuk mengakses data bisnis lain.
- **Isolasi cabang:** admin hanya dapat melihat dan mengelola data operasional cabang tempat ia ditugaskan, dengan pengecualian direktori customer dan saldo bisnis pada Bagian 7.9. Satu admin ditugaskan ke **tepat satu cabang**, tetapi satu cabang **boleh memiliki banyak admin** (shift pagi/sore).
- **Developer tidak mengakses data operasional individual** (termasuk transaksi, pelanggan, pembayaran, pencarian, dan detailnya). Panel developer hanya menampilkan data administratif tenant dan angka agregat yang disebut FR-D04; angka agregat tidak memuat baris/identitas individual.

---

## 4. Alur Pengguna Utama

### 4.1 Pelanggan mengecek status (alur terpenting)

1. Pelanggan membuka aplikasi (ketik alamat web, atau scan QR di resi).
2. Jika lewat QR → langsung masuk halaman status transaksinya. Jika lewat halaman depan → masukkan kode resi (cukup 6 karakter) → tekan "Cek Status".
3. Halaman status menampilkan: nama & telp cabang laundry, kode resi, nama pelanggan (disamarkan sebagian), tanggal masuk, estimasi selesai, rincian layanan, potongan (promo/stempel) jika ada, total akhir, rincian pembayaran (DP terbayar & sisa tagihan bila ada), status bayar, timeline status, catatan kondisi cucian (jika ada), dan jumlah stempel pelanggan (jika program stempel aktif).
4. Hanya pada `DITERIMA`/`DIPROSES` dan bisnis yang dapat menulis, pelanggan bisa memasukkan alamat email pada form "Beritahu saya jika sudah selesai".
5. Jika pelanggan kehilangan resi/kode: pelanggan menghubungi atau mendatangi laundry → admin mencari transaksinya berdasarkan nama/no. HP → mengirim ulang link status via `wa.me`.

### 4.2 Admin mencatat cucian masuk

1. Admin login → dashboard cabang.
2. Klik "Transaksi Baru" → cari pelanggan berdasarkan no. HP/nama; jika belum ada, isi nama + no. HP (email opsional).
3. Tambahkan satu atau lebih item layanan: pilih jenis layanan → isi berat (kg) atau jumlah (untuk layanan satuan-item) → isi perkiraan jumlah baju (opsional, sebagai catatan).
4. Jika pelanggan berhak (stempel cukup), sistem menawarkan **penukaran stempel**; admin juga bisa memilih **satu promo** dari daftar promo aktif. Sistem menghitung subtotal, potongan, dan total akhir otomatis. Estimasi selesai dihitung otomatis dari durasi layanan terlama (bisa dikoreksi manual).
5. Isi catatan kondisi cucian jika perlu (mis. "noda di kerah, kancing lepas 1").
6. Catat pembayaran awal: lunas penuh, uang muka/DP (jika diaktifkan owner), atau belum bayar (dibayar saat pengambilan).
7. Simpan → sistem membuat kode resi + QR → admin mencetak resi dan/atau mengirim link resi via WhatsApp.

Aturan penggunaan: jika pelanggan mencampur layanan berdurasi berbeda (mis. express + reguler) dan ingin mengambilnya pada waktu berbeda, admin membuat **resi terpisah** — satu resi selalu punya satu status.

### 4.3 Admin memperbarui status & menyerahkan cucian

1. Admin membuka daftar transaksi aktif atau memindai/mencari kode resi.
2. Ubah status ke tahap berikutnya (lihat Bagian 6).
3. Saat status menjadi **Siap Diambil** → sistem otomatis mengirim notifikasi ke pelanggan (email dan/atau WhatsApp), mencantumkan sisa tagihan bila belum lunas.
4. Saat pelanggan mengambil cucian → jika belum lunas, admin **wajib mencatat pelunasan terlebih dahulu**, baru bisa mengubah status ke **Sudah Diambil**. Bisnis laundry tidak menerima hutang: cucian yang sudah diambil selalu berstatus lunas.
5. Jika terjadi kesalahan input yang ketahuan setelah transaksi terkunci tetapi sebelum diserahkan (lihat Bagian 7.6): admin **membatalkan transaksi (wajib isi alasan) lalu membuat transaksi baru yang benar**. Tidak ada fitur buka-kunci.

### 4.4 Owner memantau & mengelola bisnis

1. Owner login → dashboard ringkasan semua cabang miliknya (transaksi & kg masuk hari ini, pendapatan hari ini, cucian siap diambil yang menumpuk, total sisa tagihan berjalan).
2. Owner membuka laporan → filter per cabang, rentang tanggal, status transaksi, status bayar.
3. Owner mengelola layanan lewat **Layanan Master**: mengubah daftar/harga master, lalu menyebarkannya ke cabang-cabang pilihan; atau menyesuaikan layanan per cabang secara langsung.
4. Owner mengelola promo, program stempel, akun admin, saklar DP, dan perilaku notifikasi dari menu pengaturan.
5. Bila perlu, owner mengerjakan operasional langsung (buat transaksi, update status) di cabang mana pun miliknya.

### 4.5 Developer mengelola tenant

1. Developer login → panel developer.
2. Mendaftarkan bisnis laundry baru: nama bisnis + akun owner (nama, email, password awal — owner wajib mengganti password saat login pertama) + masa aktif.
3. Mengonfigurasi hal teknis per bisnis: WhatsApp API (penyedia, token, nomor pengirim resmi milik bisnis) dan email (nama & alamat pengirim; opsional SMTP milik bisnis sendiri).
4. Memperpanjang/mengubah masa aktif atau menonaktifkan bisnis. Siklus masa aktif: peringatan 7 hari sebelum habis → masa tenggang 7 hari (fungsi penuh + banner mencolok) → **mode baca-saja** (lihat FR-D05).

### 4.6 Calon pemilik laundry mencoba demo

1. Prospek membuka halaman depan → klik "Coba Demo".
2. Sistem otomatis membuat **bisnis demo terpisah** berisi data contoh, lalu memasukkan prospek sebagai owner tanpa perlu mendaftar.
3. Seluruh halaman menampilkan banner "MODE DEMO", dengan tombol berpindah peran "Lihat sebagai Admin" / "Kembali sebagai Owner" (tanpa logout, khusus mode demo).
4. Notifikasi email/WhatsApp **tidak benar-benar terkirim** di mode demo (hanya dicatat di log).
5. Bisnis demo kedaluwarsa setelah 7 hari dan dibersihkan sesuai FR-M05.

---

## 5. Kebutuhan Fungsional

Label **M1–M6** = urutan pengerjaan (lihat Bagian 13). Semua kebutuhan wajib diselesaikan.

### 5.A Publik / Pelanggan (tanpa login)

| ID | Kebutuhan | Milestone |
|---|---|---|
| FR-C01 | Halaman depan menampilkan kolom input kode resi dan tombol "Cek Status" sebagai elemen utama, serta tombol "Coba Demo". | M2 |
| FR-C02 | Halaman status dapat diakses langsung via URL unik `/t/{kode_resi}` (URL inilah yang dikodekan dalam QR di resi). | M2 |
| FR-C03 | Halaman status menampilkan: timeline status (tahap yang sudah dilalui + waktu), rincian layanan, potongan promo/stempel (jika ada), total akhir, rincian pembayaran (jumlah terbayar/DP & sisa tagihan), status bayar, estimasi selesai, catatan kondisi, dan info cabang (nama, alamat, no. telp yang bisa diklik untuk telepon/WA). | M2 |
| FR-C04 | Pelanggan dapat mendaftarkan email untuk **transaksi ini saja** saat `DITERIMA`/`DIPROSES` pada bisnis yang dapat menulis. Alamat baru diverifikasi lewat tautan sekali pakai 24 jam (GET konfirmasi baca, POST mengaktifkan). Sebelum konfirmasi `notification_email` tetap; sesudahnya hanya `transactions.notification_email` berubah. Endpoint publik **tidak pernah mengubah `customers.email`**; email master hanya diedit admin/owner (FR-A13). Pemegang resi mendapat kemampuan terbatas ini, bukan otoritas atas identitas pelanggan atau transaksi lain. Permintaan/konfirmasi tunduk pada rate limit, status, dan lifecycle; aturan rinci Bagian 9.1. | M3 |
| FR-C05 | Jika kode resi tidak ditemukan, tampilkan pesan ramah "Kode resi tidak ditemukan, periksa kembali resi Anda" tanpa membocorkan informasi apa pun. | M2 |
| FR-C06 | Nama dan no. HP pelanggan di halaman publik disamarkan sebagian demi privasi (mis. "Rad*** — 0812***678"). | M2 |
| FR-C07 | Jika program stempel aktif, halaman status menampilkan saldo ledger sebenarnya (mis. "Stempel Anda: 7/10"). Jika saldo negatif akibat pencabutan perolehan lama, tampilkan nilai bertanda apa adanya (mis. `−1/10`) disertai penjelasan sederhana bahwa satu stempel perlu diperoleh kembali sebelum mendekati hadiah; jangan menyamarkan sebagai nol. | M4 |

### 5.B Admin Laundry

Catatan: seluruh kemampuan admin di bawah ini juga dimiliki owner untuk semua cabang miliknya (FR-O06).

| ID | Kebutuhan | Milestone |
|---|---|---|
| FR-A01 | Admin login dengan email + password. Ada fitur lupa password via email; selain itu owner dapat mereset password admin (FR-O03). | M1 |
| FR-A02 | Membuat transaksi baru: pilih/daftarkan pelanggan (nama + no. HP wajib, email opsional), tambah ≥1 item layanan, sistem menghitung subtotal, potongan, dan total akhir otomatis. | M2 |
| FR-A03 | Setiap item layanan berisi: jenis layanan, kuantitas (berat kg dengan 1 angka desimal untuk layanan satuan-kg, atau jumlah unit untuk layanan satuan-item), perkiraan jumlah baju (opsional), subtotal otomatis. | M2 |
| FR-A04 | Mencatat catatan kondisi cucian per transaksi (teks bebas, mis. noda/kerusakan yang sudah ada sejak diterima). | M2 |
| FR-A05 | Memperbarui status transaksi mengikuti siklus di Bagian 6. Setiap perubahan status tercatat di riwayat (siapa, kapan). | M2 |
| FR-A06 | Mencatat pembayaran sebagai **daftar catatan pembayaran** per transaksi (jumlah, metode tunai/transfer, waktu, admin pencatat). Partial payment pertama hanya boleh saat saklar DP bisnis aktif; transaksi yang sudah DP boleh ditambah sebagian atau dilunasi meski saklar kemudian mati (FR-O07). Pembayaran penuh tetap boleh pada transaksi yang sah menerima payment; total seluruh pembayaran tidak boleh melebihi total akhir. Status bayar diturunkan sesuai Bagian 7.4, termasuk total akhir Rp0 yang otomatis `LUNAS` tanpa baris pembayaran Rp0. Catatan pembayaran **tidak dapat diubah/dihapus** setelah disimpan. | M2 |
| FR-A07 | Transaksi tidak dapat diubah ke status "Sudah Diambil" jika status bayar belum `LUNAS` (bisnis tidak menerima hutang). | M2 |
| FR-A08 | Mencetak resi dalam format struk thermal 58 mm (lihat Bagian 8). | M2 |
| FR-A09 | Tombol "Kirim resi via WhatsApp" yang membuka link `wa.me/{no_hp}` berisi teks ringkasan resi + link cek status (tanpa biaya API, dikirim manual dari perangkat admin). | M2 |
| FR-A10 | Dashboard admin menampilkan: daftar transaksi hari ini, daftar transaksi aktif per status, dan daftar khusus **"Siap Diambil — belum diambil"** yang diurutkan dari yang paling lama menunggu (tampilkan umur dalam hari). | M2 |
| FR-A11 | Dashboard admin juga menampilkan daftar **"Terlambat"**: transaksi `DITERIMA`/`DIPROSES` yang sudah melewati estimasi selesai — agar admin proaktif mengabari pelanggan atau memperbarui status bila cucian memang sudah siap. | M2 |
| FR-A12 | Pencarian transaksi berdasarkan kode resi, nama pelanggan, atau no. HP. | M2 |
| FR-A13 | Kelola data pelanggan: mencari (nama/no. HP), menambah, dan **mengedit** data pelanggan (nama, no. HP, email). No. HP unik per bisnis; nama boleh sama antarpelanggan berbeda. Mengubah no. HP tidak memutus riwayat transaksi karena transaksi terkait ke ID internal pelanggan. Sejak M3, perubahan nomor juga menangani log WA atomik sesuai Bagian 9.2: pending belum pernah attempt mengikuti nomor terbaru, log yang pernah attempt tidak diretarget/retry ke nomor usang. Email transaksi tetap mengikuti FR-C04. | M2 |
| FR-A14 | Gabung pelanggan sumber→tujuan satu bisnis secara atomik setelah konfirmasi. Seluruh transaksi dan ledger sumber dipindah; identitas nama/no. HP/email tujuan dipertahankan; saldo dihitung dari ledger gabungan, termasuk negatif; sumber dihapus terakhir. Snapshot finansial/email tetap, pending verifikasi transaksi sumber dibatalkan; sejak M3 log WA ditangani menurut Bagian 9.2 sebelum nomor sumber dilepas pada commit. Owner boleh lintas cabang; admin hanya jika **seluruh transaksi kedua pelanggan** berada di cabangnya (pelanggan tanpa transaksi boleh). Jika ada transaksi cabang lain, tolak tanpa membocorkan rinciannya dan arahkan ke owner. Jejak audit menyimpan ID dan identitas sebelum/sesudah, bukan memindahkan delta ledger. Lihat Bagian 7.9. | M2 |
| FR-A15 | Membatalkan transaksi hanya dari `DITERIMA`, `DIPROSES`, atau `SIAP_DIAMBIL`, dengan alasan dan audit pelaku. `SUDAH_DIAMBIL` dan `DIBATALKAN` terminal. Pembayaran tetap tersimpan tetapi dikecualikan dari semua laporan pendapatan, termasuk periode lampau; pengembalian dana di luar aplikasi. Tambah kompensasi ledger sekali untuk perolehan/penukaran yang benar-benar ada; tidak menghapus histori. Pembatalan tidak mengirim notifikasi dan membatalkan kiriman yang belum diotorisasi ke penyedia. | M2 |
| FR-A16 | Edit item/berat/jumlah/promo/penukaran hanya pada `DITERIMA`, **belum LUNAS, tanpa payment, dan tanpa loyalty history apa pun**. Rp0 langsung LUNAS sehingga harga terkunci walau program stempel mati. Edit finansial sah memakai penawaran harga terbaru dan konfirmasi perubahan harga; semua snapshot terkait diganti atomik. Catatan kondisi/estimasi/perkiraan jumlah baju boleh diedit hanya pada `DITERIMA` tanpa hitung ulang harga. Identitas transaksi (cabang/customer/pembuat/waktu masuk/kode) tidak dapat diedit; merge adalah pengecualian customer. Sejak `DIPROSES` semua edit operasional terkunci; FR-C04 tetap boleh sebelum siap. Status/pembayaran adalah aksi terpisah sesuai matriks Bagian 6. Kesalahan sebelum penyerahan dipulihkan lewat batal+buat ulang; tidak ada buka-kunci. | M2 |
| FR-A17 | Tombol "Ingatkan pelanggan" pada transaksi Siap Diambil untuk mengirim ulang notifikasi secara manual (email dan/atau link `wa.me`). | M3 |
| FR-A18 | Menerapkan **maksimal satu promo** dan/atau **maksimal satu penukaran stempel** pada transaksi, dengan perhitungan otomatis sesuai Bagian 7.2. | M4 |

### 5.C Pemilik Laundry (Owner)

| ID | Kebutuhan | Milestone |
|---|---|---|
| FR-O01 | Owner login dengan email + password (akun dibuat developer; wajib ganti password saat login pertama). Ada fitur lupa password via email. | M1 |
| FR-O02 | Kelola cabang: tambah/ubah/nonaktifkan cabang beserta nama tempat, alamat, dan no. telepon (tampil di resi & halaman status). Cabang **tidak dapat dinonaktifkan selama masih memiliki transaksi aktif** (belum diambil dan belum dibatalkan); riwayat cabang nonaktif tetap muncul di laporan. | M1 |
| FR-O03 | Kelola akun admin: tambah/ubah/nonaktifkan admin, tugaskan ke tepat satu cabang (satu cabang boleh punya banyak admin), dan **mereset password admin**. | M1 |
| FR-O04 | Kelola **Layanan Master** di tingkat bisnis: nama layanan, satuan (per kg / per item), harga, durasi standar (jam), berat minimum (opsional, khusus satuan kg), status aktif. Layanan express bukan fitur khusus — cukup dibuat sebagai layanan dengan durasi lebih pendek dan harga lebih tinggi. | M1 |
| FR-O05 | Kelola layanan **per cabang** (struktur atribut sama dengan master; harga boleh berbeda antarcabang) dengan dua mekanisme sinkronisasi dari master: (a) di halaman cabang, tombol **"Salin/Perbarui dari Master"**; (b) di halaman master, tombol **"Sebarkan ke Cabang"** dengan daftar centang cabang tujuan. Aturan keduanya sama: layanan master yang belum ada di cabang **ditambahkan**; layanan cabang yang namanya sama dengan master **ditimpa mengikuti master**; layanan khusus cabang yang tidak ada di master **tidak disentuh**. Sebelum eksekusi, tampilkan pratinjau ("5 layanan akan diperbarui, 2 layanan baru ditambahkan") karena penyesuaian lokal pada layanan bernama sama akan hilang. Transaksi lama tidak terpengaruh (harga di-snapshot). | M1 |
| FR-O06 | Owner memiliki seluruh kemampuan operasional admin (FR-A02–A18) di **semua cabang miliknya**. | M2 |
| FR-O07 | Saklar "Terima uang muka (DP)" per bisnis menentukan izin **memulai DP saat payment dicatat**, bukan saat transaksi dibuat. Jika `business_settings.dp_enabled=false`, transaksi belum pernah dibayar tidak boleh menerima partial payment pertama; pembayaran penuh atau tetap belum bayar boleh. Transaksi yang sudah DP tetap boleh menerima cicilan berikutnya atau pelunasan selama tidak overpay. Toggle dan payment memakai business root lock yang sama; total Rp0 tetap LUNAS tanpa payment. | M2 |
| FR-O08 | Kelola promo (lihat FR-P01–P03). | M4 |
| FR-O09 | Kelola pengaturan program stempel (lihat FR-L01). | M4 |
| FR-O10 | Riwayat transaksi semua cabang miliknya dengan filter: cabang, rentang tanggal, status transaksi, status bayar (`BELUM_BAYAR`/`DP`/`LUNAS`). | M5 |
| FR-O11 | Laporan pendapatan: total pendapatan dengan filter waktu (hari ini, 7 hari terakhir, bulan ini, rentang tanggal bebas) dan per cabang. Definisi pendapatan lihat Bagian 7.5. | M5 |
| FR-O12 | Daftar tagihan berjalan: transaksi aktif berstatus `BELUM_BAYAR` atau `DP` beserta sisa tagihannya dan totalnya. | M5 |
| FR-O13 | Dashboard owner: ringkasan harian lintas cabang — jumlah transaksi & total kg masuk hari ini, pendapatan hari ini, jumlah `SIAP_DIAMBIL` dengan `waktu_siap_diambil` berusia **≥ `business_settings.reminder_first_days` hari**, total sisa tagihan berjalan. Batas ini sama dengan usia pengingat pertama (default 2 hari), dihitung pada waktu dashboard dibuka; disajikan sebagai kartu angka besar (Bagian 12.2). | M5 |
| FR-O14 | Pengaturan perilaku notifikasi: aktif/nonaktif pengingat otomatis; jeda hari pertama, interval ulang, batas maksimum pengiriman (masing-masing bilangan 1–255, termasuk saat pengingat nonaktif); **saklar WA per jenis peristiwa**; **batas maksimum pesan WA per bulan**; serta penghitung pesan WA terkirim bulan berjalan. Owner tidak pernah melihat token/konfigurasi teknis. | M3 |
| FR-O15 | Grafik pendapatan per hari/bulan pada rentang yang dipilih. | M5 |
| FR-O16 | Ekspor riwayat transaksi ke file CSV. | M5 |

### 5.D Developer

| ID | Kebutuhan | Milestone |
|---|---|---|
| FR-D01 | Developer login dengan email + password. Akun developer dibuat saat instalasi aplikasi (seeder/perintah setup). | M1 |
| FR-D02 | Mendaftarkan bisnis laundry baru: nama bisnis + akun owner (nama, email, password awal) + tanggal masa aktif. Owner wajib mengganti password saat login pertama. | M1 |
| FR-D03 | Mengubah masa aktif, menonaktifkan/mengaktifkan kembali bisnis, dan mereset password owner. | M1 |
| FR-D04 | Panel developer menampilkan daftar semua bisnis: nama, status (aktif / masa tenggang / baca-saja / nonaktif / demo), masa aktif, jumlah cabang, dan jumlah transaksi 30 hari terakhir. Developer tidak dapat membuka data operasional (transaksi, pelanggan) milik bisnis. | M1 |
| FR-D05 | Penegakan siklus masa aktif: (1) **7 hari sebelum habis** — banner peringatan owner; (2) **tenggang 7 hari** — fungsi penuh dengan banner owner/admin; (3) **baca-saja** — owner/admin tetap bisa login dan membaca, seluruh tulis bisnis ditolak server; notifikasi otomatis berhenti; `wa.me` manual tetap dapat dibuka. Logout, ganti password awal, dan lupa/reset password tetap diizinkan sebagai operasi keamanan akun. `GET /t/{kode_resi}` tetap tersedia saat `BACA_SAJA`/`NONAKTIF`, tetapi POST email FR-C04 ditolak. | M1 |
| FR-D06 | Halaman konfigurasi teknis per bisnis di panel developer: (a) WhatsApp — penyedia (adapter: Fonnte / Wablas / WhatsApp Business API resmi), token/kredensial, nomor pengirim resmi milik bisnis, saklar aktif global (default **nonaktif**); (b) Email — nama & alamat pengirim resmi bisnis; default memakai SMTP global aplikasi (dikelola developer), dengan kolom opsional SMTP milik bisnis sendiri. Konfigurasi teknis hanya dikelola developer; rahasia tersimpan tidak dikirim kembali ke form. Konfigurasi provider tambahan (Wablas server/secret, WABA phone ID/versi/template disetujui/bahasa) wajib lengkap sebelum WA aktif; kontrak di architecture Bagian 6.3. | M3 |

### 5.E Mode Demo

| ID | Kebutuhan | Milestone |
|---|---|---|
| FR-M01 | Tombol "Coba Demo" di halaman depan membuat bisnis demo baru yang terisolasi (tenant tersendiri) dan langsung memasukkan pengguna sebagai owner tanpa pendaftaran. | M6 |
| FR-M02 | Demo berisi tepat 2 cabang, satu owner dan satu admin pada cabang pertama, layanan master/cabang express+kg+item, satu promo 10% aktif, loyalti N=10 aktif, DP aktif, 6 pelanggan sintetis dan 15 transaksi realistis sesuai fixture Bagian 9.4. Semua pembayaran/status/ledger contoh konsisten; saldo tidak diisi tanpa ledger. | M6 |
| FR-M03 | Seluruh halaman mode demo menampilkan banner "MODE DEMO" dan tombol "Lihat sebagai Admin" / "Kembali sebagai Owner" tanpa logout. Provisioning membuat satu admin demo yang ditugaskan ke cabang pertama; tombol selalu memakai identitas/cabang admin demo itu. Cabang demo lain tetap bisa dikelola dari peran owner. | M6 |
| FR-M04 | Tidak ada komunikasi eksternal dari demo: email transaksi/verifikasi/reset password, WA API, maupun pembukaan `wa.me`/tautan telepon ditekan. Log transaksi per kanal eligible berisi tipe, kanal, tujuan snapshot (fixture sintetis atau input prospek), identity unik, status `ditekan_demo`; tanpa job kirim atau slot WA. UI menampilkan pratinjau simulasi. Tidak memerlukan kredensial nyata; eligibility WA demo mengikuti saklar simulasi. Tidak ada log generik tanpa kanal. | M6 |
| FR-M05 | Demo kedaluwarsa tepat `created_at + 7 × 24 jam`; sejak itu sesi/pergantian peran/tulis/status publik demo ditolak. Pembersihan berjalan setiap menit dan menghapus seluruh tenant demo beserta data/infrastruktur terkait. Pada sistem sehat selesai ≤5 menit sesudah kedaluwarsa; bila scheduler berhenti, hapus pada putaran pertama setelah pulih. Masa aktif/tenggang bisnis nyata tidak berlaku untuk demo. | M6 |
| FR-M06 | Pembuatan demo dibatasi (rate limit) per alamat IP (default: maksimal 3 demo per hari per IP) untuk mencegah penyalahgunaan. | M6 |

### 5.F Loyalti (Stempel) & Promo

| ID | Kebutuhan | Milestone |
|---|---|---|
| FR-L01 | Owner mengatur program stempel per bisnis: aktif/nonaktif, jumlah stempel yang dibutuhkan (N), jenis layanan yang digratiskan (satu layanan master satuan-kg), dan berat maksimal gratis (kg). Default row bisnis baru: nonaktif, N=10, layanan hadiah dan berat maks null. Saat diaktifkan wajib N=1–255 (N selalu dalam rentang ini walau program mati), layanan master aktif satuan kg milik bisnis, dan berat maks >0; saat nonaktif konfigurasi hadiah boleh belum lengkap. | M4 |
| FR-L02 | Saat transaksi pertama kali menjadi `LUNAS`, tambah tepat +1 bila program **aktif saat event itu** dan transaksi tidak menukar stempel. Termasuk total Rp0; peralihan status cucian bukan pemicu tambahan. Aktivasi program kemudian tidak memberi stempel retroaktif. Pembatalan mencabut perolehan yang benar-benar pernah ada, meskipun program kini nonaktif; saldo boleh negatif sesuai Bagian 7.8. | M4 |
| FR-L03 | Saat create atau edit finansial yang masih sah, pelanggan bersaldo ≥ N dapat menukar tepat satu hadiah pada satu item kg. Layanan cabang harus aktif dan namanya cocok dengan master hadiah aktif menurut aturan nama Bagian 7.8. Harga hadiah memakai **harga snapshot layanan cabang**, berat diskon memakai **berat aktual**, bukan berat minimum tertagih. Delta −N disimpan pada saat transaksi disimpan, bukan saat lunas; transaksi penukaran tidak mendapat +1. Potongan nol ditolak agar stempel tidak terbuang; sisa kuota gratis tidak disimpan. | M4 |
| FR-L04 | Riwayat stempel append-only per pelanggan (`perolehan`, `penukaran`, `pengembalian_penukaran`, `pencabutan_perolehan`; transaksi terkait; waktu) dapat dilihat admin/owner. `jumlah` menyimpan delta stempel aktual bertanda saat event (+1, −N saat penukaran, +N saat pengembalian, −1 saat pencabutan); saldo sumber kebenaran = `SUM(jumlah)`, tidak dihitung ulang memakai N terkini. `customers.stamp_count` hanya cache saldo ledger. | M4 |
| FR-P01 | Owner membuat/mengubah/menonaktifkan promo: nama, tipe potongan (persen dari `promo_eligible_base` atau nominal Rp), nilai, `minimal_total` opsional yang dibandingkan dengan `promo_eligible_base`, periode berlaku, dan cakupan cabang. Basis tersebut = subtotal − potongan stempel (Bagian 7.2). | M4 |
| FR-P02 | Saat membuat transaksi, admin dapat memilih **maksimal satu** promo dari daftar promo yang sedang aktif & berlaku di cabangnya. Sistem memvalidasi periode dan minimal transaksi. Tidak ada potongan bebas di luar promo. | M4 |
| FR-P03 | Nama, tipe, nilai, dan hasil potongan promo yang dipakai disalin (snapshot) ke transaksi, sehingga perubahan/penonaktifan promo tidak mengubah transaksi lama. | M4 |

### 5.G Notifikasi

| ID | Kebutuhan | Milestone |
|---|---|---|
| FR-N01 | Saat status berubah menjadi "Siap Diambil", sistem otomatis mengirim **email** ke `transactions.notification_email` (jika tersedia) berisi info laundry, kode resi, total akhir, sisa tagihan (bila belum lunas), dan link cek status. Dikirim satu kali per transaksi per kanal. | M3 |
| FR-N02 | Pengingat otomatis: jika transaksi masih "Siap Diambil" setelah N hari (default 2), kirim pengingat; ulangi setiap M hari (default 2) hingga maksimum K kali (default 3). Nilai N, M, K diatur owner (FR-O14). | M3 |
| FR-N03 | Notifikasi **WhatsApp otomatis** melalui lapisan adapter multi-penyedia (Fonnte / Wablas / WhatsApp Business API) memakai kredensial & nomor pengirim milik masing-masing bisnis (dikonfigurasi developer, FR-D06). Default nonaktif. Aplikasi harus berfungsi 100% tanpa WA API (email + `wa.me` manual adalah fondasi). | M3 |
| FR-N04 | Batas WA bulanan memakai slot per bisnis yang direservasi atomik: `tertunda`, `diproses`, `berhasil`, dan `perlu_pemeriksaan` memegang satu slot/key. Retry tidak menggandakan slot. Bulan kuota adalah bulan WIB **otorisasi pengiriman pertama**; retry pasti-ditolak yang sudah melewati bulan tersebut dihentikan, sebelum panggilan pertama slot yang masih pending dipindah jika bulan berganti. Setelah panggilan mungkin terjadi, bulan itu tetap. Ini batas otorisasi aplikasi, bukan waktu penerimaan/tagihan penyedia. Kuota penuh → WA `dilewati_batas`, email tetap berjalan jika ada. Owner melihat jumlah berhasil untuk bulan kuota, slot pending/tidak pasti terpisah. Null = tanpa batas, 0 = tidak ada slot; batas baru di bawah slot terpakai bulan ini ditolak. | M3 |
| FR-N05 | Semua upaya notifikasi dicatat dalam `notification_logs` (kanal, tujuan, tipe, nomor pengingat bila otomatis, waktu, status). Pembukaan `wa.me` manual berstatus `dibuka_manual` dan tidak dianggap berhasil terkirim; log otomatis per kanal menjadi sumber idempotensi. Log terlihat di detail transaksi. | M3 |
| FR-N06 | SMTP/WA dipanggil asinkron setelah commit sehingga kegagalan eksternal tidak membatalkan perubahan status. Reservasi log+job database queue atomik; lease dan scanner memulihkan job/claim hilang. Hanya kegagalan **pasti belum diterima penyedia** boleh dicoba lagi, maksimal 3 panggilan/key. Timeout/crash sesudah pengiriman mungkin dimulai → terminal otomatis `perlu_pemeriksaan`, tanpa kirim ulang otomatis. UI menjelaskan hasil belum diketahui dan menawarkan kontak manual sadar risiko. Tidak menjanjikan exactly-once eksternal; rincian Bagian 9.2. Kegagalan DB saat commit membatalkan seluruh operasi secara atomik. | M3 |

### 5.H Resi & Kode Transaksi

| ID | Kebutuhan | Milestone |
|---|---|---|
| FR-R01 | Setiap transaksi mendapat kode resi unik **6 karakter** (huruf besar + angka) yang mudah diketik manual: tanpa karakter yang mudah tertukar (tanpa `O`, `0`, `I`, `1`, `L`), dibuat acak, unik secara global. Contoh: `K7F3XA`. | M2 |
| FR-R02 | Resi memuat QR code yang berisi URL halaman status (`/t/{kode_resi}`). | M2 |
| FR-R03 | Isi resi: nama/alamat/telp cabang, kode resi, tanggal masuk, nama & no. HP pelanggan, rincian item (layanan, berat/jumlah, harga satuan, subtotal), potongan promo/stempel (jika ada), total akhir, pembayaran tercatat (DP) & sisa tagihan, status bayar, estimasi selesai, catatan kondisi, QR code, dan catatan kaki singkat (mis. "Cek status: {url}"). | M2 |
| FR-R04 | Format cetak dioptimalkan untuk printer thermal 58 mm via fungsi print browser (CSS khusus cetak). Tersedia juga tampilan resi sebagai halaman web (menyatu dengan halaman status) agar bisa dibagikan sebagai link. | M2 |

### 5.I PWA

| ID | Kebutuhan | Milestone |
|---|---|---|
| FR-W01 | Aplikasi dapat di-install ke home screen (web app manifest lengkap: nama, ikon, warna tema, splash screen) untuk admin & owner. | M6 |
| FR-W02 | Service worker sederhana: cache aset statis (network-only untuk seluruh data/HTML dinamis; hanya aset statis boleh di-cache). Cache wajib diperbarui otomatis setiap deploy (cache busting) agar pengguna tidak terjebak di tampilan lama. Tidak ada fungsi offline-first — saat offline, tampilkan halaman "Anda sedang offline". | M6 |

---

## 6. Siklus Status Cucian

Status berjalan satu arah (kecuali pembatalan):

```
DITERIMA → DIPROSES → SIAP_DIAMBIL → SUDAH_DIAMBIL
DITERIMA / DIPROSES / SIAP_DIAMBIL → DIBATALKAN (dengan alasan)
```

| Status | Arti | Pemicu |
|---|---|---|
| `DITERIMA` | Cucian diterima & tercatat | Otomatis saat transaksi dibuat |
| `DIPROSES` | Sedang dicuci/disetrika/dikerjakan | Admin |
| `SIAP_DIAMBIL` | Selesai, menunggu diambil | Admin — memicu notifikasi (FR-N01) |
| `SUDAH_DIAMBIL` | Sudah diambil pelanggan | Admin — wajib `LUNAS` terlebih dahulu (FR-A07) |
| `DIBATALKAN` | Transaksi dibatalkan | Admin/Owner, wajib isi alasan |

Aturan: hanya maju **tepat satu tahap**, tanpa lompat atau mundur; setiap perubahan dicatat di riwayat status beserta waktu dan pengguna yang mengubah. Waktu perubahan ke `SIAP_DIAMBIL` dan `SUDAH_DIAMBIL` disimpan di kolom khusus untuk keperluan pengingat dan laporan. Empat tahap ini final — jangan menambah tahap antara (mis. "Dicuci"/"Disetrika" terpisah).

---

### 6.1 Matriks aksi transaksi (owner/admin yang berwenang)

Semua aksi tulis mensyaratkan tenant writable dan cabang aktif. `aktif` berarti tiga state pertama; terminal berarti dua state terakhir.

| State | Edit finansial | Edit nonfinansial | Payment | Transisi | Notifikasi / loyalty |
|---|---|---|---|---|---|
| `DITERIMA` | Hanya belum LUNAS, tanpa payment/ledger | Catatan kondisi, estimasi, perkiraan baju | Positif ≤ sisa, aturan DP 7.4 | `DIPROSES` atau batal | +1 hanya event LUNAS; −N hanya save redemption sah; FR-C04 tersedia |
| `DIPROSES` | Tidak | Tidak | Positif ≤ sisa, aturan DP 7.4 | `SIAP_DIAMBIL` atau batal | +1 saat LUNAS jika eligible; FR-C04 tersedia |
| `SIAP_DIAMBIL` | Tidak | Tidak | Positif ≤ sisa, aturan DP 7.4 | `SUDAH_DIAMBIL` hanya LUNAS, atau batal | Ready satu kali saat masuk; pengingat otomatis/manual; +1 bila baru LUNAS |
| `SUDAH_DIAMBIL` | Tidak | Tidak | Tidak | Tidak | Tidak ada pengingat/loyalty baru; resi manual boleh |
| `DIBATALKAN` | Tidak | Tidak | Tidak | Tidak | Kompensasi ledger sekali saat masuk; log pending dihentikan; resi manual boleh dengan label batal |

Riwayat `DITERIMA` dibuat bersama transaksi. Status bayar tetap turunan pembayaran bahkan setelah batal; pembayaran/angka lama tidak dinolkan dan UI menandai "Dibatalkan — pengembalian dana di luar aplikasi". Retry pembatalan/status yang sudah sama tidak menambah riwayat, audit, atau kompensasi. Dua terminal tidak dapat dibatalkan/dibuka ulang; kesalahan sesudah penyerahan hanya ditangani di luar aplikasi. Data identitas customer/cabang pada tampilan memakai master terkini; nominal/item/promo tetap snapshot. Update master bukan edit harga transaksi.

## 7. Aturan Bisnis & Perhitungan

### 7.1 Perhitungan subtotal item

1. Item satuan **kg**: `harga_per_kg × berat_kg`. Jika layanan punya berat minimum dan berat aktual di bawahnya, subtotal dihitung memakai berat minimum. Berat dicatat sampai 1 angka desimal (contoh: 3,5 kg); hasil pecahan rupiah dibulatkan ke rupiah terdekat (0,5 ke atas) per item sebelum dijumlah.
2. Item satuan **item**: `harga_per_item × jumlah`.
3. Satu transaksi/resi boleh berisi lebih dari satu jenis layanan.
4. Semua harga dalam Rupiah, bilangan bulat, tanpa pajak (harga sudah final).
5. Nama, satuan, dan harga layanan cabang disalin ke item transaksi saat dibuat. `transaction_items.subtotal` menyimpan hasil final termasuk berat minimum yang berlaku saat itu; perubahan master/layanan kemudian tidak mengubah tampilan maupun hitungan transaksi lama.

### 7.2 Urutan perhitungan potongan & total akhir

```
subtotal          = jumlah semua subtotal item
potongan_stempel  = harga layanan gratis × min(berat item, berat_maks_gratis)   [jika ditukar]
promo_eligible_base = subtotal − potongan_stempel
potongan_promo    = (nilai_persen / 100) × promo_eligible_base, atau min(nilai_nominal, promo_eligible_base) [jika promo sah]
total_akhir       = subtotal − potongan_stempel − potongan_promo   (minimal 0)
```

Urutan validasi: hitung subtotal dan potongan stempel (maksimal nilai item hadiah), tentukan `promo_eligible_base`, lalu uji status/periode/cabang promo dan `promo_eligible_base >= minimal_total` bila minimum diisi. Baru hitung potongan promo. Potongan stempel dan persen dibulatkan ke rupiah terdekat (0,5 ke atas) dan dibatasi masing-masing pada nilai item hadiah dan `promo_eligible_base`. Nilai yang ditagih adalah `total_akhir ≥ 0`; `promo_eligible_base` merupakan nilai turunan, bukan kolom wajib.

### 7.3 Estimasi selesai

`estimasi_selesai = waktu_masuk + durasi_terlama` di antara semua layanan dalam transaksi (durasi diatur owner per layanan). Admin dapat mengubah estimasi secara manual **selama `DITERIMA`** bila perlu (mis. antrean penuh); sejak `DIPROSES` estimasi terkunci. Transaksi yang melewati estimasi tanpa mencapai "Siap Diambil" masuk daftar "Terlambat" (FR-A11).

### 7.4 Aturan pembayaran & uang muka (DP)

1. Pembayaran dicatat sebagai daftar catatan pembayaran per transaksi (jumlah, metode tunai/transfer, waktu, pencatat). Satu transaksi bisa punya beberapa catatan (mis. DP saat masuk + pelunasan saat ambil).
2. Status bayar diturunkan otomatis: bila `total_akhir = 0`, `LUNAS` tanpa baris pembayaran Rp0; bila total akhir > 0 dan terbayar = 0, `BELUM_BAYAR`; bila 0 < terbayar < total akhir, `DP`; bila terbayar = total akhir, `LUNAS`. Total terbayar tidak boleh melebihi total akhir dan baris pembayaran harus > 0.
3. Untuk `total_akhir > 0`, hitung `total_paid = SUM(payments.jumlah)` sebelum payment di bawah business root lock. Payment positif yang membuat `total_paid_after == total_akhir` selalu boleh jika transaksi masih sah menerima payment. Jika `total_paid == 0` dan `0 < total_paid_after < total_akhir`, `business_settings.dp_enabled` **harus true saat payment dicatat**. Jika `0 < total_paid < total_akhir`, cicilan berikutnya maupun pelunasan tetap boleh meski saklar false. Aturan sama untuk payment awal saat create dan payment tambahan pada ketiga state aktif; tidak ada snapshot izin DP per transaksi.
4. **Bisnis tidak menerima hutang:** pelunasan wajib tercatat paling lambat saat pengambilan; transaksi `SUDAH_DIAMBIL` selalu `LUNAS`.
5. Catatan pembayaran bersifat permanen — tidak dapat diubah atau dihapus. Kesalahan pencatatan dipulihkan lewat aturan Bagian 7.6.
6. Resi, halaman status, dan notifikasi menampilkan jumlah terbayar dan sisa tagihan bila belum lunas.
7. Stempel loyalti diberikan saat pertama mencapai `LUNAS` jika program aktif pada event tersebut (FR-L02), termasuk transaksi Rp0 tanpa pembayaran, kecuali transaksi yang memakai penukaran stempel.
8. Pencatatan pembayaran wajib atomik: ikuti urutan lock bisnis → customer → transaksi (`SELECT ... FOR UPDATE`), baca ulang saklar DP, hitung ulang total pembayaran dan sisa, validasi nominal, sisipkan pembayaran, turunkan status bayar, proses stempel, lalu commit. Toggle DP juga mengambil business root lock terlebih dahulu. Toggle-off commit dahulu → partial pertama ditolak; partial pertama commit dahulu → transaksi sudah DP dan cicilan berikutnya tetap sah. Dua pembayaran paralel tidak boleh bersama-sama melampaui sisa. Replay request payment yang sudah berhasil tetap mengembalikan hasil lama sebelum mengevaluasi izin payment baru, tanpa insert kedua.

### 7.5 Definisi pendapatan (untuk laporan)

- **Pendapatan** = jumlah seluruh **catatan pembayaran yang diterima** pada periode laporan, berdasarkan tanggal pembayaran — bukan per transaksi. Dengan demikian DP yang diterima bulan ini dan pelunasannya bulan depan tercatat di bulannya masing-masing.
- Pembayaran milik transaksi `DIBATALKAN` **dikeluarkan** dari laporan pendapatan (pengembalian dana terjadi di luar aplikasi dan tidak dicatat).
- **Tagihan berjalan** = jumlah sisa tagihan (total akhir − terbayar) dari transaksi aktif berstatus `BELUM_BAYAR`/`DP`.

### 7.6 Aturan pemulihan kesalahan (final)

Selama `DITERIMA`, edit field harga hanya boleh sebelum LUNAS, sebelum ada pembayaran **dan** sebelum ada `loyalty_histories` apa pun untuk transaksi tersebut; pemeriksaan ini dilakukan ulang secara atomik saat menyimpan. Semua transaksi Rp0 terkunci karena LUNAS, termasuk tanpa payment dan saat loyalti nonaktif. Catatan kondisi dan estimasi selesai dapat diubah selama `DITERIMA`. Seluruh field operasional terkunci sejak `DIPROSES` dan pembayaran permanen bagi semua peran; perubahan `notification_email` sesudah konfirmasi FR-C04 adalah pengecualian sempit untuk preferensi notifikasi, hanya sampai sebelum `SIAP_DIAMBIL` saat tenant dapat menulis. Kesalahan setelah kunci harga/status tetapi sebelum SUDAH_DIAMBIL dipulihkan hanya lewat **batalkan (wajib alasan) → buat transaksi baru**. Pembatalan mengeluarkan pembayaran dari laporan, menambah `pengembalian_penukaran` sebesar stempel yang dahulu ditukar, dan/atau `pencabutan_perolehan` untuk stempel yang pernah diperoleh; entry lama tetap ada. Jika pencabutan perolehan lama membuat saldo di bawah nol karena stempel sudah dipakai pada transaksi lain, saldo bertanda tetap dicatat dan penukaran baru ditolak sampai saldo cukup. Jangan membangun buka-kunci atau pembatalan pembayaran.

Penukaran stempel memakai transaksi DB dengan lock bisnis lebih dahulu, kemudian baris customer (`SELECT ... FOR UPDATE`), memeriksa saldo ledger/cache sesudah lock, lalu menulis delta negatif aktual yang berlaku saat itu. Dua penukaran paralel tidak boleh sama-sama membelanjakan saldo yang sama.

### 7.7 Aturan resi terpisah

Satu resi memiliki tepat satu status. Cucian dengan waktu pengambilan berbeda (mis. express + reguler) dicatat sebagai resi terpisah. Tidak ada status per item.

---

### 7.8 Harga, snapshot, dan loyalti yang deterministik

- Harga satuan layanan wajib rupiah bulat positif; berat kg 0,1–9.999,9 dengan satu desimal; jumlah unit 1–65.535; durasi 1–65.535 jam. Berat minimum null atau positif pada kg, null pada item. Maksimum 100 baris item/transaksi, setiap nilai uang dan total maksimal 4.294.967.295; overflow ditolak, bukan dipotong. Perkiraan baju null atau 0–65.535, tidak memengaruhi harga. Perhitungan integer per 0,1 kg, half-up, tanpa floating point.
- Nama layanan disimpan dengan trim dan spasi berulang dijadikan satu; unik tanpa membedakan huruf besar/kecil, tetapi membedakan aksen. Penukaran mencocokkan nama master hadiah dengan layanan cabang menurut aturan ini serta satuan kg; layanan yang tidak cocok tidak eligible. Rename master tidak otomatis me-rename cabang. Hadiah belum tersedia di cabang → tidak ditawarkan; sinkronisasi/penyesuaian owner diperlukan. Master yang sedang menjadi hadiah aktif tidak dapat dinonaktifkan/diubah ke item sebelum program dimatikan atau hadiah diganti.
- Bila beberapa baris kg cocok, admin memilih tepat satu baris hadiah. `harga_snapshot` baris itu menjadi basis potongan. Misal aktual 2 kg, minimum 3 kg, harga Rp7.000, hadiah maks 3 kg: subtotal Rp21.000, potongan Rp14.000, sisa Rp7.000 sebelum promo. UI menjelaskan sisa berasal dari minimum berat. Potongan tidak pernah lebih dari subtotal item.
- Create dan edit finansial yang sah selalu memvalidasi katalog/promo terkini dan mengisi ulang **seluruh** snapshot harga, berat minimum, durasi, hadiah dan promo. Preview server dikonfirmasi pengguna; bila katalog atau hasil berubah sebelum save, tolak 409 dan tampilkan preview baru. Perubahan master saja tidak pernah mengubah transaksi tersimpan. Edit nonfinansial tidak menghitung ulang harga. Estimasi pada create/edit finansial dihitung dari durasi snapshot terlama kecuali nilai estimasi manual dikirim; estimasi wajib ≥ waktu masuk.
- Promo persen 1–100 atau nominal positif, `mulai <= selesai` inklusif tanggal WIB; minimum null/0 berarti tanpa minimum. Promo sebagian cabang wajib ≥1 cabang bisnis sendiri. Master promo dinonaktifkan, tidak dihapus lewat UI. Promo yang sama pada edit finansial tetap divalidasi ulang terhadap konfigurasi/periode kini.
- Program nonaktif menghentikan perolehan dan penukaran **baru**, menyembunyikan saldo publik, tetapi mempertahankan saldo/ledger/hadiah transaksi lama. Kompensasi pembatalan selalu dijalankan. Aktivasi ulang tidak memberi perolehan retroaktif pada transaksi yang sudah LUNAS. N baru hanya untuk penukaran berikutnya; pengembalian selalu kebalikan delta asal. Penukaran dan perolehan saling eksklusif pada satu transaksi.
- Saldo = SUM seluruh delta aktual. Pencabutan +1 yang sudah dibelanjakan boleh membuat saldo negatif; tidak membatalkan transaksi pemakai stempel lain. Perolehan berikutnya mengurangi utang stempel; penukaran baru hanya jika saldo ≥ N. Panel dan publik saat aktif menampilkan nilai bertanda serta keterangan kekurangan `N - saldo`, bukan clamp ke nol.

### 7.9 Identitas, cabang, dan merge

- Customer adalah direktori bisnis bersama: admin boleh mencari, menambah, mengedit nama/no. HP/email, melihat saldo total, dan memakai customer di cabangnya. Daftar/detail transaksi, payment, log, dan baris ledger yang disertakan hanya milik cabangnya; saldo total tidak memberi hak membuka sumber dari cabang lain. Owner melihat seluruh bisnis. Admin tidak mendapat report/export owner.
- Merge menolak source=target, beda bisnis, source sudah hilang, atau cabang tak berwenang. Batas admin FR-A14 diperiksa ulang saat commit, termasuk transaksi historis/batal kedua pihak. Pindahkan transaksi aktif dan historis serta semua ledger; email snapshot, payment, dan harga tetap. Verifikasi pending pada transaksi source dibatalkan; pending transaksi target tetap karena hanya berpengaruh ke transaksi tersebut. Sejak M3, edit nomor maupun merge menangani seluruh log WA terkait sesuai Bagian 9.2 di bawah business lock yang sama, sebelum source dihapus terakhir dan nomor lama dilepas pada commit. Nomor source boleh didaftarkan lagi sebagai pelanggan baru tanpa histori lama dan tanpa otorisasi kiriman baru dari log transaksi customer lama.
- Create transaksi baru pada cabang nonaktif ditolak; penonaktifan cabang bersaing dengan create harus menghasilkan salah satu urutan sah, tidak boleh cabang nonaktif memiliki transaksi aktif. Admin pada cabang nonaktif ditolak akses panel; owner tetap dapat membaca riwayat dan mengaktifkan kembali cabang. Ganti penugasan/nonaktifkan admin berlaku pada request berikutnya, termasuk sesi yang sudah login.
- Nomor customer/cabang dinormalisasi dari `08…`/`+62…`/`62…` dengan menghapus spasi, tanda hubung, dan kurung; hasil wajib `62` diikuti 8–13 digit (digit pertama sesudah62 bukan0). Email di-trim/lowercase, format email valid maksimal150 karakter; input tetap ditolak bila melebihi batas kolom, bukan dipotong.
- Waktu masuk/payment/history berasal dari jam server WIB saat aksi diterima dalam transaksi DB; tidak dapat di-backdate oleh client. Create/payment memakai identitas request unik agar double-click atau retry setelah respons hilang tidak menggandakan transaksi/pembayaran. Request key sama dengan payload berbeda ditolak 409. Edit memakai versi transaksi agar dua tab lama tidak saling menimpa.
- Sinkronisasi hanya master aktif ke cabang aktif pilihan; nama sama ditimpa seluruh atribut termasuk mengaktifkan kembali layanan lokal nonaktif, nama berbeda ditambah, layanan lokal lain tetap. Rename master berarti nama baru, bukan rename cabang otomatis. Preview mencakup perubahan/penambahan/reaktivasi; perubahan sejak preview menolak save 409 dan meminta konfirmasi baru. Semua cabang pilihan tersimpan atomik.

### 7.10 Definisi dashboard, laporan, dan ekspor

- Rentang tanggal memakai WIB `[awal 00.00, sehari setelah akhir 00.00)`. "7 hari terakhir" = hari ini + 6 hari sebelumnya. Bulan ini mulai tanggal 1. Riwayat/CSV/filter tanggal memakai `waktu_masuk`; pendapatan/grafik memakai `payments.waktu` dan mengecualikan transaksi yang **kini** batal. Pembatalan dapat mengubah laporan periode lampau secara sengaja; tidak ada refund ledger.
- Transaksi hari ini (daftar admin) mencakup semua yang masuk hari ini dengan label status termasuk batal; **kartu jumlah transaksi dan kg masuk** mengecualikan batal. Kg masuk menjumlah berat aktual item kg, bukan minimum tertagih, bukan jumlah unit atau perkiraan baju. Agregasi tidak menggandakan item saat join payment.
- Aktif = `DITERIMA`/`DIPROSES`/`SIAP_DIAMBIL`; tagihan berjalan menjumlah sisa positif ketiganya. Terlambat = `DITERIMA`/`DIPROSES` dan `estimasi_selesai < now`. Daftar siap diambil mencakup semua `SIAP_DIAMBIL`, urut waktu siap lalu ID; umur = floor(detik menunggu/86400). Kartu menumpuk memakai ≥ `reminder_first_days × 24 jam` walaupun pengingat dimatikan; N selalu positif.
- Statistik developer: jumlah seluruh cabang (termasuk nonaktif), jumlah seluruh transaksi dengan `waktu_masuk >= now - 30 × 24 jam` dan `<= now`, termasuk batal; tidak ada drill-down. Demo dilabeli terpisah.
- CSV satu baris/transaksi: kode resi, nama cabang, nama/no. HP customer terkini, status, status bayar, subtotal, potongan stempel/promo, total, total terbayar, sisa, waktu masuk, estimasi, waktu siap/diambil. Semua status dapat diekspor sesuai filter; batal tetap berlabel dan bukan pendapatan. Angka/grafik/CSV konsisten dalam satu snapshot baca per request; tidak ada cache agregat lintas request. CSV memakai UTF-8 BOM, RFC 4180, tanggal ISO WIB, nilai uang numerik tanpa `Rp`, teks berawalan formula dinetralkan.

## 8. Kebutuhan Cetak Resi

- Ukuran kertas: struk thermal 58 mm (lebar cetak efektif ±48 mm). Gunakan CSS `@media print` dengan lebar tetap; hindari elemen yang tidak perlu saat cetak.
- Resi dicetak dari browser (dialog print). Tidak perlu integrasi driver printer khusus.
- Resi juga tersedia sebagai halaman web (menyatu dengan halaman status) agar bisa dibagikan sebagai link.

---

## 9. Aturan Notifikasi & Pengingat

| Peristiwa | Kanal | Waktu |
|---|---|---|
| Status → `SIAP_DIAMBIL` | Email (jika email ada) + WA otomatis (jika aktif untuk peristiwa ini & batas bulanan belum tercapai) | Seketika, satu kali |
| Pengingat belum diambil | Email + WA otomatis (dengan syarat yang sama) | H+N setelah siap diambil, ulang tiap M hari, maks K kali (default N=2, M=2, K=3) |
| Kirim manual oleh admin | Link `wa.me` resi; email/`wa.me` pengingat | Resi pada semua status; pengingat hanya `SIAP_DIAMBIL`; tunduk lifecycle dan demo (FR-A17, FR-A09) |

Ketentuan teknis (log di bagian ini untuk komunikasi terkait transaksi; email keamanan akun memakai alur terpisah Bagian 9.3):

- Notifikasi instan dikirim lewat antrean (queue) dengan **driver database**. Perubahan status, reservasi log per kanal, dan job queue terkait disimpan dalam transaksi database yang sama agar tidak ada log `tertunda` tanpa job setelah commit. Pengiriman ke SMTP/WA tetap terjadi setelah commit sehingga kegagalan kirim tidak membatalkan status transaksi. Tidak dibutuhkan Redis.
- Pengingat "belum diambil" dijalankan oleh **penjadwal (cron) sekali sehari** yang memindai transaksi `SIAP_DIAMBIL` dan mengirim sesuai aturan.
- Pembagian tanggung jawab konfigurasi: **developer memegang yang teknis** (penyedia WA, token, nomor pengirim, email pengirim, SMTP — FR-D06); **owner memegang yang perilaku** (pengingat, saklar per peristiwa, batas WA bulanan — FR-O14).
- Nomor HP disimpan dalam format internasional (mis. `62812xxxxxxx`) agar kompatibel dengan `wa.me` dan API WhatsApp.
- Di mode demo, seluruh pengiriman ditekan — setiap kanal yang eligible dicatat terpisah dengan tujuan dan status `ditekan_demo` (FR-M04). Di mode baca-saja, notifikasi otomatis berhenti (FR-D05).
- Email tujuan setiap transaksi berasal dari `transactions.notification_email`: disalin dari `customers.email` saat transaksi dibuat. FR-C04 menyimpan alamat calon perubahan terpisah dan mengirim tautan verifikasi ke alamat itu; baru setelah konfirmasi valid, hanya email transaksi diperbarui atomik; email master customer tidak disentuh. Tautan lama/kedaluwarsa atau konfirmasi sesudah `SIAP_DIAMBIL` ditolak. Perubahan customer pada masa depan tidak mengubah snapshot transaksi lama.
- Setiap notifikasi otomatis memiliki identitas logis per transaksi, tipe, kanal, dan nomor pengingat (untuk `SIAP_DIAMBIL` gunakan nomor 0). `notification_logs` menjadi penjaga idempotensi per kanal; retry memakai identitas yang sama. Worker yang mati setelah claim sebelum mengirim dipulihkan lewat lease; jika hasil kirim mungkin sudah diterima penyedia, jangan mengirim ulang secara buta. Pembukaan `wa.me` manual hanya dicatat `dibuka_manual`, bukan `berhasil`; status sukses WA otomatis hanya dari respons penyedia.
- Di mode baca-saja, link `wa.me` manual masih dapat dibuka dari data yang sudah tampil, tetapi tidak membuat log baru; pengiriman ulang email manual adalah tulis bisnis dan ditolak. Pada mode tulis, pembukaan `wa.me` dicatat mulai M3 ketika `notification_logs` tersedia.

---

### 9.1 Email publik dan batas informasi

`customers.email` adalah kontak master yang dicatat admin/owner, bukan otomatis email terverifikasi. Saat create, nilainya disalin ke `notification_email`; koreksi master kemudian tidak mengubah transaksi lama. Form publik menyimpan pending+versi, antre email verifikasi, lalu POST konfirmasi bertanda tangan mengubah **hanya** snapshot transaksi dan menghapus pending. Permintaan baru menggugurkan tautan lama; siap/batal/merge source menggugurkan pending. Konfirmasi tidak mengirim ready retroaktif. GET verifikasi tidak memiliki side effect selain metadata sesi keamanan.

Semua jalur resolusi resi (form depan, URL langsung, versi cetak publik, permintaan/konfirmasi email) berbagi batas 30/menit/IP; permintaan email juga 3/jam/resi, 10/hari/IP, 5/jam/alamat tujuan. Batas berlaku sebelum pengecekan keberadaan kode. Kode dinormalisasi trim+uppercase lalu harus tepat 6 karakter alfabet FR-R01. Pesan gagal tidak mengungkap email aktif/pending atau ID internal. Kode resi adalah capability terbatas, bukan login customer.

Publik hanya melihat whitelist FR-C03/C06/C07; nama maksimal tiga karakter pertama + `***` (nama ≤3 karakter hanya karakter pertama), no. HP maksimal empat digit awal + `***` + tiga digit akhir. Tidak ada email, ID internal, nama operator, tujuan log, histori ledger, atau tautan transaksi lain. Catatan kondisi adalah informasi publik: form admin memberi petunjuk agar tidak memasukkan identitas/kontak/rahasia. Cetak publik tetap masked; cetak internal berisi identitas utuh hanya lewat route panel yang berwenang. Respons dinamis dan dokumen cetak memakai `Cache-Control: no-store`, `Referrer-Policy: no-referrer`, `noindex`; QR lokal dan tanpa analytics/aset pihak ketiga.

### 9.2 Pengiriman, pemulihan, dan pengingat

- Ready direservasi sekali per kanal pada transisi; retry memakai key yang sama. Kanal tidak eligible tidak dibuat log. Tidak ada email fallback kedua karena email sudah independen dari WA. Jika tidak ada email dan WA tidak eligible/penuh, log/UI memperlihatkan kanal yang tersedia dan admin dapat menghubungi manual.
- Pengingat harian 08.00 WIB memakai N/M/K terkini: pertama `now >= waktu_siap_diambil + N×24 jam`; berikutnya `now >= last_reminder_at + M×24 jam` dan `reminder_count < K`. Maksimal satu nomor baru per transaksi per putaran; scheduler terlambat tidak mengejar semua nomor sekaligus. Counter bertambah **saat reservasi**, bukan sukses, sehingga gagal/tidak pasti/kuota habis tetap menghabiskan satu nomor. Bila sama sekali tidak ada kanal eligible, counter tidak bertambah. Mengubah pengaturan tidak mereset nomor; menaikkan K dapat menambah pengingat selanjutnya. Menonaktifkan pengingat menghentikan pending otomatis bertipe pengingat, bukan email manual/ready.
- Tepat sebelum otorisasi panggilan, worker memeriksa lifecycle, status masih `SIAP_DIAMBIL` (atau versi pending untuk verifikasi), saklar relevan, dan demo. Pending yang tak relevan menjadi terminal `dilewati_kondisi` dengan alasan. Penonaktifan/saklar off, penyerahan, dan pembatalan menandai log belum diotorisasi sebagai dilewati_kondisi; saat reaktivasi dari BACA_SAJA, pending lama ditutup terlebih dahulu. Tidak direplay ketika tenant diaktifkan kembali; pengingat berikutnya mengikuti jadwal. Isi pesan dibentuk dari keadaan terkini saat otorisasi, tujuan dari snapshot log; setelah itu payload tidak berubah. Payment yang masuk setelah otorisasi dapat membuat sisa pada pesan tertinggal; link status selalu terkini. Pembatalan/penyerahan/nonaktif sesudah otorisasi tidak dapat menarik kembali pesan in-flight; UI tidak menjamin penarikan pesan eksternal.
- Claim tanpa panggilan memiliki lease 5 menit. Crash sebelum penanda panggilan aman dipulihkan; crash/timeout sesudah penanda (bahkan bila belum benar-benar terhubung) menjadi `perlu_pemeriksaan`, tanpa retry otomatis. Status itu terminal bagi otomasi dan mempertahankan slot WA bulan asal. UI: "Hasil pengiriman belum diketahui. Periksa dengan pelanggan sebelum mengirim manual." Tidak ada aksi mengubahnya menjadi gagal/melepas slot berdasarkan dugaan. Kegagalan yang pasti ditolak penyedia dapat retry 60 lalu 300 detik, maksimal 3 panggilan total; metadata alasan aman ditampilkan, bukan raw response/credential.
- Email pengingat manual hanya pada `SIAP_DIAMBIL`, memakai request key unik, jarak minimal 10 menit antarpermintaan baru per transaksi dan maksimal 20/hari/user; double-click/retry key sama tidak membuat log/kirim baru. Key baru berarti tindakan kirim baru yang disadari; jika ada hasil tidak pasti, tampilkan peringatan di atas sebelum konfirmasi. Manual tidak mengubah counter pengingat otomatis. `wa.me` manual tidak memakai API/kuota; pada bisnis writable dicatat sebelum membuka link, pada baca-saja link langsung tanpa tulis log; di demo hanya simulasi `ditekan_demo`.
- Tujuan WA awal disalin dari nomor customer saat reservasi. **Belum pernah attempt** berarti `delivery_started_at IS NULL` **dan** `attempt_count == 0`; null marker saja tidak cukup karena retry pasti-ditolak dapat mengosongkannya. Saat edit nomor atau merge source→target, log otomatis WA `tertunda`/`diproses` yang belum pernah attempt dan masih eligible diperbarui ke nomor terbaru/target secara atomik; `notification_key`, nomor pengingat, dan slot kuota tidak digandakan. Claim lama dibatalkan sehingga worker wajib membaca ulang tujuan saat preflight, bukan memakai salinan di memori. Jika sudah tidak eligible, terminalkan sesuai alasan existing tanpa send.
- Jika marker ada **atau** `attempt_count > 0`, recipient/payload tidak diretarget. Bila nomor kini berbeda dari tujuan log, log nonterminal atau unknown dengan token finalisasi menjadi `perlu_pemeriksaan` (`recipient_berubah`), token dibatalkan, snapshot/attempt/bucket asal dipertahankan; tidak ada retry ke nomor lama maupun pengalihan ke nomor baru. Hasil terlambat dari worker lama tidak boleh menimpa review tersebut. Log terminal historis lain tidak diubah atau dibuka ulang. Preflight/retry/recovery juga memeriksa kesamaan nomor customer transaksi terkini, bukan mencari customer berdasarkan nomor snapshot; perubahan A→B→A tidak menghidupkan log terminal kembali. Pesan yang sudah diotorisasi/in-flight dapat tetap diterima provider dan tidak dapat ditarik kembali; jaminan ini menghentikan otorisasi/send ulang sesudah perubahan, bukan membatalkan efek eksternal yang sudah berjalan. Kontak baru hanya dari event baru yang sah atau aksi manual baru dengan identity baru sesuai aturan existing.
- Aturan perubahan nomor ini hanya untuk WA API, bukan `transactions.notification_email`, tujuan log email, atau histori pembukaan `wa.me`. Email tetap snapshot per transaksi sesuai FR-C04; log dan identity dipertahankan sepanjang umur transaksi. Penyesuaian internal log WA customer bersama mencakup seluruh cabang bisnis tanpa memberi admin akses melihat log/transaksi cabang lain; batas otorisasi merge tetap FR-A14.

### 9.2.1 Restore-safe outbound hold

Konsistensi database hasil restore berbeda dari deduplikasi efek eksternal. **Sistem tidak menjamin deduplikasi terhadap notifikasi yang berhasil dikirim setelah titik backup tetapi hilang dari database akibat restore (window RPO).** Tidak adanya log pada backup bukan bukti provider belum menerima pesan.

Sebelum aplikasi/worker hasil restore boleh mengakses provider, operator menetapkan `OUTBOUND_RESTORE_HOLD=true` dalam konfigurasi deployment di luar database backup. Selama hold, seluruh panggilan SMTP/WA aplikasi diblokir, termasuk otomatis, reminder, pending/recovered/retry, email manual, verifikasi, dan email keamanan akun; worker non-outbound serta operasi bisnis yang tidak membutuhkan outbound boleh berjalan. Hold tidak membatalkan pesan yang sudah keluar sebelum insiden; operator menghentikan instance/worker lama sebelum restore. Tidak ada reservasi outbound baru selama hold; status/payment tetap dapat commit, request email ditolak/ditunda secara jelas tanpa menjanjikan kiriman. Job lama tidak boleh menjadi jalan melewati hold.

Operator memeriksa recovery point dan periode hilang dengan bukti yang tersedia (bersama owner bila menyentuh data operasional; tanpa menambah hak panel developer), menandai log nonterminal asal backup/hold `perlu_pemeriksaan`, membatalkan token claim, dan mempertahankan slot WA yang sudah ada. Jangan menebak acceptance provider dari backup. Sebelum re-enable, tetapkan `outbound_resume_at` pada konfigurasi deployment bersama pelepasan hold; detail penghentian worker dan batas timestamp mengikuti arsitektur Bagian 9. Hanya event baru **setelah** batas re-enable boleh mengirim. Tidak ada replay event historis, job lama, atau catch-up reminder dari transaksi yang sudah siap sebelum batas itu; tindakan kontak manual baru yang disadari sesudah rekonsiliasi tetap mengikuti state/kuota/identity existing. Re-enable tidak mengembalikan pengetahuan tentang send yang hilang dan bukan jaminan exactly-once melintasi restore. Tidak memerlukan tabel atau infrastruktur deduplikasi eksternal baru.

### 9.3 Matriks lifecycle tenant dan akun

Urutan prioritas: `is_active=false` → NONAKTIF; demo belum kedaluwarsa → DEMO; demo kedaluwarsa → ditolak menunggu purge; bisnis nyata menggunakan tanggal WIB. AKTIF sampai akhir `active_until`; TENGGANG tanggal berikutnya sampai akhir tanggal `active_until+7`; BACA_SAJA mulai tanggal `+8`. Peringatan owner mulai tanggal `active_until−7`. Pengubahan masa aktif berlaku pada request/preflight berikutnya, tanpa menunggu cron.

| Aksi | AKTIF / TENGGANG | BACA_SAJA | NONAKTIF | DEMO belum kedaluwarsa |
|---|---|---|---|---|
| Login / GET panel | Ya | Ya | Tidak; sesi lama juga ditolak | Ya lewat sesi demo |
| Tulis bisnis | Ya | 423 | 423 | Ya |
| GET status publik | Ya | Ya | Ya | Ya, banner demo |
| Request / konfirmasi email publik | Ya pada state sah | 423 | 423 | Simulasi verifikasi saja, tidak mengaktifkan email tanpa verifikasi |
| Notifikasi otomatis / email manual | Ya jika eligible | Berhenti | Berhenti | Log simulasi, tidak keluar |
| `wa.me` manual dari panel | Ya + log sejak M3 | Ya tanpa log | Panel tidak tersedia | Pratinjau tanpa link keluar |
| Ganti password (termasuk wajib awal) | Ya | Ya | Sesi diblokir; gunakan reset | Tidak untuk akun demo yang dicadangkan |
| Lupa/reset password | Ya | Ya | Ya untuk akun nyata; tidak mengaktifkan akun/bisnis | Respons generik, tanpa kiriman/token reset |
| Logout | Ya | Ya | Ya | Ya |

`must_change_password` membolehkan hanya halaman/POST ganti password dan logout di panel; tidak membatasi halaman publik. Reset password nyata satu kali 60 menit, respons permintaan generik; email keamanan selalu dari SMTP global lewat job terenkripsi sekali panggil, bukan notification_logs transaksi atau kuota WA; permintaan ulang pengguna mengganti token lama; ganti password meregenerasi sesi dan mencabut sesi lain/token reset lama; reset link mencabut seluruh sesi dan menuntut login ulang. Password minimal 12 karakter dan maksimal 72 byte UTF-8 (batas bcrypt); remember-me tidak disediakan. Reset oleh owner/developer menetapkan password sementara input operator dengan `must_change_password=true`, tidak mengirim otomatis; aksi reset diaudit, nilai password tidak masuk audit/log. Akun nonaktif tidak dapat login walau reset berhasil. Admin nonaktif atau cabang nonaktif juga diblokir pada sesi lama; developer tetap dapat mengelola administrasi bisnis NONAKTIF.

### 9.4 Demo dan milestone

Fixture demo: tepat dua cabang bernama "Cabang Utama" lalu "Cabang Kedua"; satu owner dan satu admin khusus Cabang Utama. Tiga layanan master/cabang: Cuci+Setrika Rp7.000/kg (48 jam, minimum 3 kg), Express Rp12.000/kg (6 jam, minimum 3 kg), Bed Cover Rp25.000/item (48 jam). Hadiah Cuci+Setrika maks 3 kg, N=10; promo "Demo Hemat" 10% untuk semua cabang berlaku tanggal pembuatan hingga kedaluwarsa. Enam customer sintetis: customer pertama memiliki sepuluh transaksi SUDAH_DIAMBIL lunas positif (lima/cabang), ledger +10; lima lainnya masing-masing memiliki DITERIMA DP, DIPROSES belum bayar, SIAP_DIAMBIL DP berusia 3 hari, SIAP_DIAMBIL LUNAS berusia 5 hari (+1), dan DIBATALKAN setelah lunas (+1 lalu −1). Seluruh timestamp berurutan dan ledger terkait transaksi; saldo tidak di-seed terpisah. Tanggal data contoh boleh sebelum tanggal provision agar daftar menumpuk terlihat, khusus fixture internal. Email sintetis domain `.invalid` dan nomor sintetis, tidak pernah dipakai keluar aplikasi.

Pergantian peran hanya POST bersesi+CSRF yang terikat pasangan owner/admin tenant demo itu di server, bukan parameter user/branch. Akun demo yang dicadangkan dibuat dengan password acak yang tidak dibagikan, must_change_password=false, hanya login dari sesi provision/switch; tidak menerima login kredensial/reset/ganti password. Akun ini dan Cabang Utama tidak dapat dinonaktifkan/dipindah selama demo; owner masih dapat mencoba CRUD akun/cabang lain. Session ID diregenerasi saat berganti peran; tenant/cabang diperiksa setiap request. Saat kedaluwarsa semua akses demo termasuk GET resi mengembalikan pesan demo berakhir; purge tidak pernah berjalan untuk tenant normal.

Milestone tetap M1–M6: M1 menyiapkan default settings termasuk loyalti nonaktif; M2 memakai diskon nol dan belum memberi ledger, kontrak transaksi sudah menjaga kunci; M3 menambah notifikasi; M4 baru mengaktifkan promo/stempel tanpa memberi stempel retroaktif transaksi lama; M5 laporan; M6 demo/PWA. Tombol Coba Demo FR-C01 sudah tampil M2 dengan label belum tersedia sampai M6; tidak memanggil endpoint yang belum ada. Semua milestone wajib selesai. Default teknis baru bukan pemindahan milestone fitur.

## 10. Kebutuhan Data (Model Data Tingkat Tinggi)

Ini gambaran entitas utama — kontrak kolom/constraint ada di `database-schema.md` dan wajib diikuti. **Semua data operasional terikat ke satu bisnis (tenant) dan wajib difilter berdasarkan bisnis pada setiap kueri.**

- **businesses** (tenant) — nama bisnis, status aktif, masa aktif (tanggal), penanda demo (`is_demo`), waktu kedaluwarsa demo; konfigurasi teknis (penyedia WA, kredensial, nomor pengirim, saklar WA global; nama & alamat email pengirim, SMTP opsional).
- **users** — nama, email, password (hash), peran (`developer` / `owner` / `admin`), bisnis (kecuali developer), cabang penugasan (khusus admin), status aktif, penanda wajib ganti password.
- **branches** — bisnis, nama tempat, alamat, no. telepon, status aktif.
- **master_services** — bisnis; nama, satuan (`kg`/`item`), harga, durasi (jam), berat minimum (opsional), status aktif.
- **services** — bisnis dan cabang; atribut sama dengan master; diisi manual atau lewat mekanisme salin/sebarkan dari master (FR-O05).
- **customers** — bisnis, nama, no. HP (unik per bisnis), email (opsional), jumlah stempel. Berlaku lintas cabang dalam satu bisnis.
- **transactions** — bisnis, kode resi (unik global), cabang, pelanggan, pembuat, `notification_email` snapshot beserta email calon perubahan dan metadata verifikasi sementara, status, waktu masuk, estimasi selesai, waktu siap/diambil, subtotal, potongan stempel, promo terpakai (referensi + snapshot nama/tipe/nilai), potongan promo, total akhir, status bayar turunan, catatan kondisi, alasan pembatalan.
- **payments** — bisnis, transaksi, jumlah, metode (`tunai`/`transfer`), waktu, pencatat. Tidak dapat diubah/dihapus.
- **transaction_items** — transaksi, layanan, nama/satuan/harga snapshot, berat kg / jumlah unit, perkiraan jumlah baju (opsional), penanda item gratis-stempel, subtotal final. Tenant diturunkan dari transaksi.
- **promos** — bisnis, nama, tipe (`persen`/`nominal`), nilai, minimal total (opsional), tanggal mulai, tanggal selesai, cabang berlaku (semua/sebagian), status aktif.
- **loyalty_settings** — bisnis, aktif/nonaktif, N stempel, layanan gratis (referensi ke layanan master), berat maksimal gratis.
- **loyalty_histories** — bisnis, pelanggan, jenis empat peristiwa FR-L04, delta stempel aktual bertanda, transaksi terkait, waktu.
- **status_histories** — bisnis, transaksi, status, waktu, pengguna pengubah.
- **notification_logs** — bisnis, transaksi, kanal (`email`/`whatsapp`/`whatsapp_manual`), tipe (`siap_diambil`/`pengingat`/`resi`/`verifikasi_email`), nomor pengingat/identitas logis, tujuan, status kirim termasuk `dibuka_manual`, waktu. Penghitung WA berhasil memakai bulan kuota `wa_quota_month`; slot pending/tidak pasti ditampilkan terpisah.
- **business_settings** — pengaturan perilaku per bisnis: saklar DP, konfigurasi pengingat (N/M/K), saklar WA per peristiwa, batas WA bulanan.
- **audit_logs** — bisnis (nullable hanya untuk aksi developer tanpa tenant tertentu), pelaku, aksi, detail, waktu; aksi berisiko tetap berjejak.

---

## 11. Teknologi

Stack berikut adalah keputusan, bukan sekadar rekomendasi:

1. **Backend:** PHP 8.4 + Laravel 12 + MySQL 8.4. Manfaatkan fasilitas bawaan: autentikasi & otorisasi per peran, queue driver database, penjadwal cron, sistem notifikasi email, dan testing.
2. **Frontend panel (developer, owner, admin):** Inertia.js + React (TypeScript) + Tailwind CSS + shadcn/ui — dipilih agar antarmuka owner bisa didesain berkualitas konsumen (bukan tampilan panel admin generik), mengikuti aturan desain Bagian 12.2.
3. **Halaman publik (halaman depan, cek status, resi):** halaman ringan terpisah dari bundle panel — Blade + CSS ringan + JavaScript vanila seperlunya — agar cepat dimuat di HP pelanggan dengan koneksi lambat.
4. **Pendukung:** paket pembuat QR code (mis. simple-qrcode), queue database + cron (Bagian 9), PWA ringan (FR-W01–W02).
5. **Deployment:** Docker di VPS di belakang reverse proxy (mis. Nginx Proxy Manager), CI/CD via GitHub Actions.
6. **Notifikasi WA:** lapisan adapter multi-penyedia (Fonnte / Wablas / WhatsApp Business API) — jangan mengikat kode ke satu vendor.

---

## 12. Kebutuhan Non-Fungsional

### 12.1 Umum

1. **Bahasa & lokal:** seluruh antarmuka berbahasa Indonesia; mata uang Rupiah (format `Rp12.500`); zona waktu Asia/Jakarta (WIB).
2. **Responsif & mobile-first:** halaman pelanggan dan dashboard admin harus nyaman dipakai di HP; halaman cek status ringan dan cepat dimuat pada koneksi lambat.
3. **Isolasi multi-tenant (paling kritis):** seluruh kueri data operasional wajib difilter berdasarkan bisnis; tidak ada jalur akses lintas bisnis lewat URL, API, maupun pencarian. Wajib ada pengujian otomatis untuk isolasi tenant dan isolasi cabang.
4. **Keamanan:** password di-hash; halaman admin/owner/developer dilindungi login & pembatasan peran; pembatasan laju (rate limit) pada pengecekan kode resi (mencegah tebak-tebakan kode) dan pembuatan demo; data pelanggan di halaman publik disamarkan.
5. **Keandalan:** perubahan status, perhitungan potongan, dan pencatatan pembayaran bersifat atomik; notifikasi asinkron agar tidak menghambat operasional.
6. **Auditabilitas:** riwayat status, catatan pembayaran, riwayat stempel, log penggabungan pelanggan, dan log notifikasi tersimpan dan bisa dilihat di detail terkait.
7. **Disaster restore:** backup memulihkan konsistensi database sesuai RPO/RTO NFR AND-05, bukan bukti deduplikasi efek eksternal yang hilang. Outbound restore hold dan rekonsiliasi sebelum re-enable wajib mengikuti Bagian 9.2.1; tanpa replay otomatis window yang tidak diketahui.

### 12.2 Aturan desain antarmuka owner (wajib)

Target pengguna owner umumnya bukan orang muda dan bukan orang teknis. Antarmuka owner wajib mengikuti:

1. Ukuran teks besar (dasar minimal setara 16px) dan kontras tinggi.
2. Maksimal 5–6 menu utama; hindari menu bertingkat dalam.
3. Dashboard berbentuk **kartu angka besar** ("Pendapatan Hari Ini: Rp450.000"), bukan tabel padat.
4. Satu aksi utama per halaman; tombol aksi jelas dan besar.
5. Istilah bahasa Indonesia sehari-hari, tanpa jargon teknis (tidak ada kata "tenant", "API", "snapshot" di antarmuka owner).
6. Konfirmasi eksplisit untuk aksi berisiko (menimpa layanan dari master, menonaktifkan cabang, membatalkan transaksi).
7. Panel admin boleh lebih padat dan cepat (dipakai setiap hari); panel developer boleh polos/fungsional.

---

## 13. Urutan Pengerjaan (Milestone)

Semua milestone wajib diselesaikan; label ini hanya menentukan urutan pembangunan dan pengujian.

| Milestone | Cakupan | Kebutuhan |
|---|---|---|
| **M1 — Fondasi & Tenant** | Struktur multi-tenant, autentikasi semua peran, panel developer, siklus masa aktif (peringatan → tenggang → baca-saja), cabang, admin, layanan master + layanan cabang + mekanisme salin/sebarkan | FR-A01, FR-O01–O05, FR-D01–D05 |
| **M2 — Operasional Inti** | Pelanggan, transaksi, perhitungan harga, siklus status, pembayaran + DP, aturan kunci & pemulihan, resi + QR + cetak, halaman publik, dashboard admin (termasuk daftar menumpuk & terlambat) | FR-C01–C03, C05–C06, FR-A02–A16, FR-O06–O07, FR-R01–R04 |
| **M3 — Notifikasi** | Email siap-diambil, pengingat terjadwal, pencatatan pembukaan `wa.me` manual (tautan resi sudah tersedia M2), adapter WA + saklar per peristiwa + batas bulanan, log, konfigurasi teknis developer, pengaturan perilaku owner | FR-C04, FR-A17, FR-O14, FR-D06, FR-N01–N06 |
| **M4 — Loyalti & Promo** | Program stempel, promo, penerapan pada transaksi & resi | FR-C07, FR-A18, FR-O08–O09, FR-L01–L04, FR-P01–P03 |
| **M5 — Laporan Owner** | Riwayat, pendapatan berbasis pembayaran, tagihan berjalan, dashboard & ringkasan harian, grafik, ekspor CSV | FR-O10–O13, FR-O15–O16 |
| **M6 — Mode Demo & PWA** | Pembuatan demo otomatis, data contoh, banner & ganti peran, penekanan notifikasi, pembersihan terjadwal, rate limit; PWA ringan | FR-M01–M06, FR-W01–W02 |

---

## 14. Di Luar Cakupan

Fitur berikut **sengaja tidak dibangun**. Jangan mengimplementasikan, menyiapkan struktur data khusus, atau menambahkan menu untuk hal-hal ini:

- Layanan antar-jemput (pickup & delivery) beserta ongkosnya.
- Pembayaran online (QRIS/payment gateway), termasuk pembayaran langganan bisnis di dalam aplikasi — aktivasi bisnis dilakukan manual oleh developer.
- Pencatatan pengeluaran (deterjen, listrik, gaji) dan laporan laba-rugi.
- Manajemen karyawan (absensi, gaji, komisi).
- Pendaftaran mandiri bisnis laundry (self-signup) — pendaftaran hanya lewat developer.
- Akun/login untuk pelanggan.
- Aplikasi mobile native (aplikasi ini berbasis web responsif + PWA ringan).
- **PWA offline-first** (input data saat offline lalu sinkronisasi) — kompleks dan rawan konflik data; saat offline cukup tampilkan halaman "Anda sedang offline".
- Web Push sebagai kanal notifikasi pelanggan — tidak realistis mengharapkan pelanggan meng-install PWA & memberi izin; kanal pelanggan adalah email + WhatsApp.
- Diskon bebas oleh admin di luar promo yang dibuat owner.
- Fitur buka-kunci transaksi terproses, edit paksa, atau pembatalan catatan pembayaran (lihat Bagian 7.6).
- Tahapan status tambahan di luar empat tahap pada Bagian 6.

---

## 15. Keputusan yang Sudah Ditetapkan

1. Aplikasi **multi-tenant**: banyak bisnis laundry dalam satu aplikasi; hanya developer yang bisa mendaftarkan bisnis baru; masa aktif diatur manual oleh developer; pembayaran langganan terjadi di luar aplikasi.
2. Siklus masa aktif: peringatan 7 hari sebelum habis → tenggang 7 hari (fungsi penuh) → **mode baca-saja** (login & lihat boleh, tulis bisnis diblokir, operasi keamanan akun tetap boleh, notifikasi otomatis berhenti, `wa.me` manual tetap boleh). Halaman publik status tetap dapat dibaca saat bisnis baca-saja/nonaktif, tetapi form tulis email ditolak.
3. Pelanggan **tidak memiliki akun** — identitasnya nama + no. HP yang dicatat admin; no. HP unik per bisnis; nama boleh kembar; transaksi terikat ke ID internal pelanggan sehingga penggantian no. HP tidak memutus riwayat.
4. Satu admin ditugaskan ke tepat satu cabang; satu cabang boleh punya banyak admin; owner juga memegang seluruh fungsi operasional di semua cabangnya.
5. Layanan dikelola lewat **Layanan Master** tingkat bisnis + layanan per cabang; sinkronisasi memakai aturan timpa-berdasarkan-nama dengan pratinjau; layanan khusus cabang tidak disentuh; harga antarcabang boleh berbeda.
6. Pembayaran dicatat **manual** (tunai/transfer) sebagai daftar catatan pembayaran permanen; **DP didukung dan bisa dimatikan owner**; bisnis tidak menerima hutang — cucian hanya diserahkan setelah lunas.
7. Pendapatan dihitung **berbasis pembayaran yang diterima** (tanggal bayar), bukan per transaksi; pembayaran transaksi batal dikeluarkan dari laporan.
8. Edit harga transaksi `DITERIMA` hanya sebelum LUNAS, pembayaran, dan riwayat stempel apa pun; edit catatan/estimasi masih boleh saat `DITERIMA`. Field operasional terkunci sejak `DIPROSES` untuk semua peran, dengan pengecualian konfirmasi email notifikasi FR-C04 sebelum siap diambil; pemulihan sebelum penyerahan lewat batalkan + buat ulang; sesudah SUDAH_DIAMBIL tidak ada pembatalan.
9. Estimasi selesai dihitung otomatis dari durasi layanan terlama, dapat dikoreksi manual oleh admin; layanan express hanyalah jenis layanan berdurasi pendek berharga lebih tinggi.
10. Loyalti memakai model **stempel**: 1 stempel per transaksi `LUNAS`; hadiah 1x layanan gratis berbatas berat maksimal; kelebihan berat dibayar; sisa kuota hangus; transaksi penukaran tidak menambah stempel; maksimal satu penukaran per transaksi.
11. Diskon hanya lewat **promo yang dibuat owner** — admin tidak bisa memberi potongan bebas; maksimal satu promo per transaksi; promo dan harga di-snapshot ke transaksi.
12. Kanal notifikasi gratis (email + `wa.me` manual) adalah fondasi; **WA otomatis opsional** (default nonaktif) lewat adapter multi-penyedia dengan saklar per peristiwa dan batas bulanan yang jatuh ke email bila terlampaui.
13. Pembagian konfigurasi: **developer memegang semua yang teknis** (kredensial WA, nomor pengirim, email pengirim, SMTP), **owner memegang semua yang perilaku** (pengingat, saklar peristiwa, batas bulanan, saklar DP).
14. Kode resi 6 karakter acak tanpa karakter membingungkan, demi kemudahan input manual oleh pelanggan.
15. Demo memakai model **tenant demo otomatis & terisolasi** per prospek, kedaluwarsa 7 hari, notifikasi ditekan, rate limit per IP.
16. Stack: Laravel 12 + Inertia.js + React (TypeScript) + Tailwind + shadcn/ui + MySQL; halaman publik ringan terpisah; PWA ringan tanpa offline-first.
17. Nama "CekLaundry" bersifat sementara sampai pemilik produk menuntaskan pengecekan domain & merek; jangan menunda pembangunan karena nama.

---

## 16. Kriteria Penerimaan Utama

1. **Cek status:** memasukkan kode resi valid menampilkan halaman status lengkap dalam < 3 detik; kode tidak valid menampilkan pesan "tidak ditemukan" tanpa informasi lain; scan QR pada resi langsung membuka halaman status transaksi tersebut.
2. **Perhitungan harga:** transaksi berisi "Cuci+Setrika 3,5 kg @Rp7.000" dan "Bed Cover 2 item @Rp25.000" menghasilkan subtotal Rp24.500 dan Rp50.000 dengan total Rp74.500; berat 2 kg pada layanan berminimal 3 kg dihitung sebagai 3 kg.
3. **DP:** pada transaksi bertotal Rp74.500, DP Rp30.000 saat saklar aktif membuat status bayar `DP` dan sisa Rp44.500 tampil di resi, halaman status, dan notifikasi; pelunasan Rp44.500 saat pengambilan mengubah status ke `LUNAS` dan membuka izin "Sudah Diambil"; jika DP diterima bulan Juli dan pelunasan bulan Agustus, laporan Juli mencatat Rp30.000 dan Agustus Rp44.500; overpay ditolak. Saklar off menolak partial pertama untuk semua transaksi belum dibayar, termasuk dibuat saat on; bayar penuh tetap boleh. DP yang sudah berjalan tetap boleh dicicil lagi atau dilunasi. Toggle vs partial pertama mengikuti urutan commit business root lock (US-206).
4. **Kunci permanen:** pada `DITERIMA` belum LUNAS tanpa pembayaran/riwayat stempel, item dan promo boleh diedit. Begitu ada salah satunya, edit harga ditolak, termasuk total awal Rp100.000 dibayar Rp80.000 lalu dicoba turun ke Rp60.000, atau total Rp0 yang sudah menghasilkan +1 stempel tanpa payment. Catatan/estimasi masih dapat diedit saat `DITERIMA`. Sejak `DIPROSES`, semua edit operasional ditolak; pembayaran tetap permanen; batalkan + buat ulang mengeluarkan transaksi batal dari pendapatan dan mencatat alasan/pelaku di audit.
5. **Layanan master:** cabang dengan harga lokal berbeda pada layanan bernama sama akan mengikuti harga master setelah "Salin/Perbarui dari Master", dengan pratinjau tampil sebelum eksekusi; layanan khusus cabang tetap utuh; transaksi lama tidak berubah.
6. **Stempel:** dengan N=10 dan hadiah "Cuci+Setrika maks 3 kg" @Rp7.000, pelanggan bersaldo 10 membawa 5 kg mendapat potongan Rp21.000, membayar sisa, dan ledger mencatat −10; transaksi penukaran tidak menambah stempel. Saat transaksi ditukar dibatalkan, ledger menambah +10 tanpa menghapus entry −10. Saldo = `SUM(jumlah)`, termasuk setelah N diubah; dua penukaran paralel tidak menghabiskan saldo dua kali. Jika pencabutan perolehan lama menghasilkan −1, halaman status menampilkan `−1/10` dengan penjelasan sederhana, bukan nol.
7. **Promo:** `minimal_total` diuji terhadap subtotal setelah potongan stempel; promo persen dan nominal dihitung dari basis yang sama, nominal dibatasi basis, total tidak negatif. Promo kedaluwarsa/di luar cabang/di bawah minimum ditolak; snapshot nama/tipe/nilai dan hasil potongan tetap utuh setelah master berubah.
8. **Notifikasi & batas WA:** tiap peristiwa otomatis dibedakan per kanal dan nomor pengingat; reservasi log+job atomik, lease worker yang mati dipulihkan, retry tidak mengirim ulang kanal yang sudah berhasil/hasilnya belum pasti. Pada batas 100 dengan 99 slot terpakai, dua reservasi WA paralel hanya memberi satu slot; lainnya email saja dan WA `dilewati_batas`. Demo mencatat `ditekan_demo` per kanal eligible. `wa.me` manual tercatat `dibuka_manual`.
9. **Masa aktif & baca-saja:** setelah tenggang, login dan ganti/reset password tetap dapat dilakukan; tulis bisnis ditolak server, notifikasi otomatis berhenti; `GET /t/{kode_resi}` tetap terbuka pada tenant baca-saja/nonaktif, sedangkan POST email ditolak.
10. **Isolasi:** owner/admin bisnis A tidak dapat melihat/mengubah data bisnis B, dan admin cabang 1 tidak dapat melihat/mengubah transaksi cabang 2, termasuk lewat manipulasi URL atau parameter.
11. **Demo:** klik "Coba Demo" menghasilkan lingkungan demo lengkap dan langsung masuk sebagai owner; tombol admin selalu memakai admin demo cabang pertama meski demo memiliki dua cabang; tidak ada email/WA nyata terkirim; demo terhapus otomatis setelah 7 hari; pembuatan demo dari IP yang sama dibatasi.
12. **Resi & PWA:** hasil cetak thermal 58 mm terbaca rapi dengan QR terpindai dan mencantumkan DP/sisa bila ada; aplikasi bisa di-install ke home screen; setelah deploy, aset terbaru dipakai pada navigasi aman; form belum disimpan tidak dibuang diam-diam.
13. **Total Rp0 & pembayaran paralel:** total akhir Rp0 langsung `LUNAS` tanpa payment Rp0; edit harga ditolak meski tanpa payment dan meski loyalti nonaktif. Dua pembayaran bersamaan terhadap sisa Rp50.000 tidak boleh menghasilkan total terbayar Rp100.000.
14. **Email transaksi:** email customer saat pembuatan disalin ke transaksi. Permintaan email publik tidak mengubah data sampai tautan 24 jam dikonfirmasi; sesudahnya snapshot transaksi berubah atomik; email master customer tetap. Tautan kedaluwarsa/terpakai atau transaksi sudah siap diambil ditolak. Perubahan customer sesudahnya tidak mengubah snapshot lama.
15. **Integritas tenant & dashboard:** bisnis pada transaksi, cabang, customer, layanan, pembayaran, status, notifikasi, dan ledger harus cocok; owner/admin tidak dapat keluar dari bisnis/cabang. Kartu menumpuk menghitung `SIAP_DIAMBIL` berumur ≥ `reminder_first_days` hari (default 2).
16. **Gabung pelanggan & default loyalti:** gabung sumber→tujuan mempertahankan nama/no. HP/email tujuan dan menghitung ulang saldo dari history gabungan. Bisnis baru memiliki satu pengaturan loyalti nonaktif dengan N=10 dan hadiah kosong; aktivasi tanpa layanan kg serta berat maks positif ditolak.

## 17. Glosarium

| Istilah | Arti |
|---|---|
| Tenant / bisnis | Satu bisnis laundry beserta seluruh datanya, terisolasi dari bisnis lain |
| Masa aktif | Batas tanggal berlakunya akses sebuah bisnis, diatur manual oleh developer |
| Masa tenggang | 7 hari setelah masa aktif habis; fungsi masih penuh dengan banner peringatan |
| Mode baca-saja | Kondisi setelah tenggang habis: login & melihat boleh, tulis bisnis diblokir; operasi keamanan akun tetap tersedia |
| Kode resi | Kode unik 6 karakter per transaksi untuk cek status, tercetak di resi |
| Resi | Struk bukti penerimaan cucian |
| DP / uang muka | Pembayaran sebagian di awal; status bayar `DP` hingga dilunasi |
| Layanan Master | Daftar layanan tingkat bisnis yang menjadi acuan penyalinan/pembaruan layanan cabang |
| Stempel | Satuan loyalti: 1 stempel per transaksi lunas; N stempel ditukar 1x layanan gratis berbatas berat |
| Promo | Potongan harga bernama yang dibuat owner dan dipilih admin saat transaksi |
| Snapshot | Nilai disalin saat create atau edit finansial yang masih sah; tidak berubah hanya karena master diubah |
| Layanan satuan kg / item | Layanan yang dihitung per kilogram vs per buah |
| Mode demo | Tenant sementara berisi data contoh untuk calon pemilik laundry, kedaluwarsa 7 hari |
| PWA ringan | Aplikasi web yang bisa di-install ke home screen dengan cache aset, tanpa kemampuan offline-first |
| `wa.me` | Link resmi WhatsApp untuk membuka chat ke nomor tertentu tanpa API berbayar |

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
- **Isolasi cabang:** admin hanya dapat melihat dan mengelola data cabang tempat ia ditugaskan. Satu admin ditugaskan ke **tepat satu cabang**, tetapi satu cabang **boleh memiliki banyak admin** (shift pagi/sore).
- **Developer tidak mengakses data operasional individual** (termasuk transaksi, pelanggan, pembayaran, pencarian, dan detailnya). Panel developer hanya menampilkan data administratif tenant dan angka agregat yang disebut FR-D04; angka agregat tidak memuat baris/identitas individual.

---

## 4. Alur Pengguna Utama

### 4.1 Pelanggan mengecek status (alur terpenting)

1. Pelanggan membuka aplikasi (ketik alamat web, atau scan QR di resi).
2. Jika lewat QR → langsung masuk halaman status transaksinya. Jika lewat halaman depan → masukkan kode resi (cukup 6 karakter) → tekan "Cek Status".
3. Halaman status menampilkan: nama & telp cabang laundry, kode resi, nama pelanggan (disamarkan sebagian), tanggal masuk, estimasi selesai, rincian layanan, potongan (promo/stempel) jika ada, total akhir, rincian pembayaran (DP terbayar & sisa tagihan bila ada), status bayar, timeline status, catatan kondisi cucian (jika ada), dan jumlah stempel pelanggan (jika program stempel aktif).
4. Jika status belum "Siap Diambil", pelanggan bisa memasukkan alamat email pada form "Beritahu saya jika sudah selesai".
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
5. Jika terjadi kesalahan input yang ketahuan setelah transaksi terkunci (lihat Bagian 7.6): admin **membatalkan transaksi (wajib isi alasan) lalu membuat transaksi baru yang benar**. Tidak ada fitur buka-kunci.

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
5. Bisnis demo terhapus otomatis 7 hari setelah dibuat.

---

## 5. Kebutuhan Fungsional

Label **M1–M6** = urutan pengerjaan (lihat Bagian 13). Semua kebutuhan wajib diselesaikan.

### 5.A Publik / Pelanggan (tanpa login)

| ID | Kebutuhan | Milestone |
|---|---|---|
| FR-C01 | Halaman depan menampilkan kolom input kode resi dan tombol "Cek Status" sebagai elemen utama, serta tombol "Coba Demo". | M2 |
| FR-C02 | Halaman status dapat diakses langsung via URL unik `/t/{kode_resi}` (URL inilah yang dikodekan dalam QR di resi). | M2 |
| FR-C03 | Halaman status menampilkan: timeline status (tahap yang sudah dilalui + waktu), rincian layanan, potongan promo/stempel (jika ada), total akhir, rincian pembayaran (jumlah terbayar/DP & sisa tagihan), status bayar, estimasi selesai, catatan kondisi, dan info cabang (nama, alamat, no. telp yang bisa diklik untuk telepon/WA). | M2 |
| FR-C04 | Pelanggan dapat memasukkan alamat email di halaman status sebelum `SIAP_DIAMBIL` untuk menerima notifikasi. Email valid disimpan serentak ke `transactions.notification_email` dan `customers.email`; notifikasi transaksi memakai snapshot `notification_email`. Form dan POST hanya tersedia saat tenant dapat menulis; `BACA_SAJA`/`NONAKTIF` tetap menampilkan halaman status tetapi menolak perubahan email di server. | M3 |
| FR-C05 | Jika kode resi tidak ditemukan, tampilkan pesan ramah "Kode resi tidak ditemukan, periksa kembali resi Anda" tanpa membocorkan informasi apa pun. | M2 |
| FR-C06 | Nama dan no. HP pelanggan di halaman publik disamarkan sebagian demi privasi (mis. "Rad*** — 0812***678"). | M2 |
| FR-C07 | Jika program stempel bisnis aktif, halaman status menampilkan jumlah stempel pelanggan (mis. "Stempel Anda: 7/10"). | M4 |

### 5.B Admin Laundry

Catatan: seluruh kemampuan admin di bawah ini juga dimiliki owner untuk semua cabang miliknya (FR-O06).

| ID | Kebutuhan | Milestone |
|---|---|---|
| FR-A01 | Admin login dengan email + password. Ada fitur lupa password via email; selain itu owner dapat mereset password admin (FR-O03). | M1 |
| FR-A02 | Membuat transaksi baru: pilih/daftarkan pelanggan (nama + no. HP wajib, email opsional), tambah ≥1 item layanan, sistem menghitung subtotal, potongan, dan total akhir otomatis. | M2 |
| FR-A03 | Setiap item layanan berisi: jenis layanan, kuantitas (berat kg dengan 1 angka desimal untuk layanan satuan-kg, atau jumlah unit untuk layanan satuan-item), perkiraan jumlah baju (opsional), subtotal otomatis. | M2 |
| FR-A04 | Mencatat catatan kondisi cucian per transaksi (teks bebas, mis. noda/kerusakan yang sudah ada sejak diterima). | M2 |
| FR-A05 | Memperbarui status transaksi mengikuti siklus di Bagian 6. Setiap perubahan status tercatat di riwayat (siapa, kapan). | M2 |
| FR-A06 | Mencatat pembayaran sebagai **daftar catatan pembayaran** per transaksi (jumlah, metode tunai/transfer, waktu, admin pencatat). Jika saklar DP bisnis aktif, jumlah boleh sebagian (uang muka); total seluruh pembayaran tidak boleh melebihi total akhir. Status bayar diturunkan sesuai Bagian 7.4, termasuk total akhir Rp0 yang otomatis `LUNAS` tanpa baris pembayaran Rp0. Catatan pembayaran **tidak dapat diubah/dihapus** setelah disimpan. | M2 |
| FR-A07 | Transaksi tidak dapat diubah ke status "Sudah Diambil" jika status bayar belum `LUNAS` (bisnis tidak menerima hutang). | M2 |
| FR-A08 | Mencetak resi dalam format struk thermal 58 mm (lihat Bagian 8). | M2 |
| FR-A09 | Tombol "Kirim resi via WhatsApp" yang membuka link `wa.me/{no_hp}` berisi teks ringkasan resi + link cek status (tanpa biaya API, dikirim manual dari perangkat admin). | M2 |
| FR-A10 | Dashboard admin menampilkan: daftar transaksi hari ini, daftar transaksi aktif per status, dan daftar khusus **"Siap Diambil — belum diambil"** yang diurutkan dari yang paling lama menunggu (tampilkan umur dalam hari). | M2 |
| FR-A11 | Dashboard admin juga menampilkan daftar **"Terlambat"**: transaksi yang sudah melewati estimasi selesai tetapi belum berstatus "Siap Diambil" — agar admin proaktif mengabari pelanggan atau memperbarui status bila cucian memang sudah siap. | M2 |
| FR-A12 | Pencarian transaksi berdasarkan kode resi, nama pelanggan, atau no. HP. | M2 |
| FR-A13 | Kelola data pelanggan: mencari (nama/no. HP), menambah, dan **mengedit** data pelanggan (nama, no. HP, email). No. HP unik per bisnis; nama boleh sama antarpelanggan berbeda. Mengubah no. HP tidak memutus riwayat transaksi karena transaksi terkait ke ID internal pelanggan. | M2 |
| FR-A14 | Menggabungkan pelanggan duplikat: memilih pelanggan sumber & tujuan pada bisnis yang sama; transaksi dan seluruh riwayat stempel sumber pindah ke tujuan. Nama, no. HP, dan email akhir tetap milik tujuan; saldo stempel tujuan dihitung ulang dari ledger gabungan. Pemindahan hanya mengubah FK pemilik history, tidak jenis/jumlah/waktu event. Sumber dihapus setelah pemindahan atomik. Aksi tercatat dan wajib dikonfirmasi. | M2 |
| FR-A15 | Membatalkan transaksi (status "Dibatalkan") disertai alasan wajib. Transaksi keluar dari pendapatan dan pembayarannya dikeluarkan dari laporan (pengembalian dana di luar aplikasi). Stempel yang diperoleh dicabut; stempel yang ditukar dikembalikan lewat entry baru di ledger, tanpa menghapus entry lama. | M2 |
| FR-A16 | Selama `DITERIMA`, perubahan item, berat/jumlah, promo, dan penukaran stempel hanya boleh jika **belum ada pembayaran dan belum ada penukaran stempel**. Setelah salah satunya ada, seluruh field yang memengaruhi harga terkunci; `catatan_kondisi` dan `estimasi_selesai` tetap dapat diedit selama `DITERIMA`. Sejak `DIPROSES`, seluruh field operasional transaksi terkunci permanen bagi semua peran; satu-satunya pengecualian adalah pengisian email notifikasi FR-C04 sebelum `SIAP_DIAMBIL`. Pemulihan kesalahan: batalkan + buat transaksi baru (Bagian 7.6); tidak ada buka-kunci atau pembatalan catatan pembayaran. | M2 |
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
| FR-O07 | Saklar **"Terima uang muka (DP)"** per bisnis. Jika dimatikan: transaksi baru hanya bisa dicatat lunas penuh atau belum bayar; transaksi ber-DP yang sudah berjalan tetap dapat dilunasi. | M2 |
| FR-O08 | Kelola promo (lihat FR-P01–P03). | M4 |
| FR-O09 | Kelola pengaturan program stempel (lihat FR-L01). | M4 |
| FR-O10 | Riwayat transaksi semua cabang miliknya dengan filter: cabang, rentang tanggal, status transaksi, status bayar (`BELUM_BAYAR`/`DP`/`LUNAS`). | M5 |
| FR-O11 | Laporan pendapatan: total pendapatan dengan filter waktu (hari ini, 7 hari terakhir, bulan ini, rentang tanggal bebas) dan per cabang. Definisi pendapatan lihat Bagian 7.5. | M5 |
| FR-O12 | Daftar tagihan berjalan: transaksi aktif berstatus `BELUM_BAYAR` atau `DP` beserta sisa tagihannya dan totalnya. | M5 |
| FR-O13 | Dashboard owner: ringkasan harian lintas cabang — jumlah transaksi & total kg masuk hari ini, pendapatan hari ini, jumlah `SIAP_DIAMBIL` dengan `waktu_siap_diambil` berusia **≥ `business_settings.reminder_first_days` hari**, total sisa tagihan berjalan. Batas ini sama dengan usia pengingat pertama (default 2 hari), dihitung pada waktu dashboard dibuka; disajikan sebagai kartu angka besar (Bagian 12.2). | M5 |
| FR-O14 | Pengaturan perilaku notifikasi: aktif/nonaktif pengingat otomatis; jeda hari pertama, interval ulang, batas maksimum pengiriman (masing-masing bilangan ≥1 saat pengingat aktif); **saklar WA per jenis peristiwa**; **batas maksimum pesan WA per bulan**; serta penghitung pesan WA terkirim bulan berjalan. Owner tidak pernah melihat token/konfigurasi teknis. | M3 |
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
| FR-D06 | Halaman konfigurasi teknis per bisnis di panel developer: (a) WhatsApp — penyedia (adapter: Fonnte / Wablas / WhatsApp Business API resmi), token/kredensial, nomor pengirim resmi milik bisnis, saklar aktif global (default **nonaktif**); (b) Email — nama & alamat pengirim resmi bisnis; default memakai SMTP global aplikasi (dikelola developer), dengan kolom opsional SMTP milik bisnis sendiri. Seluruh konfigurasi teknis hanya terlihat & terubah oleh developer. | M3 |

### 5.E Mode Demo

| ID | Kebutuhan | Milestone |
|---|---|---|
| FR-M01 | Tombol "Coba Demo" di halaman depan membuat bisnis demo baru yang terisolasi (tenant tersendiri) dan langsung memasukkan pengguna sebagai owner tanpa pendaftaran. | M6 |
| FR-M02 | Bisnis demo otomatis berisi data contoh: 1–2 cabang, layanan master + layanan cabang (termasuk contoh express, per kg, dan per item), 1 promo aktif, program stempel aktif, saklar DP aktif, beberapa pelanggan, dan transaksi dalam berbagai status (termasuk satu transaksi ber-DP). | M6 |
| FR-M03 | Seluruh halaman mode demo menampilkan banner "MODE DEMO" yang jelas, berisi tombol berpindah peran "Lihat sebagai Admin" / "Kembali sebagai Owner" tanpa logout (hanya tersedia di mode demo). | M6 |
| FR-M04 | Di mode demo, notifikasi email/WhatsApp tidak benar-benar terkirim — hanya dicatat di log notifikasi agar alurnya tetap bisa didemonstrasikan. | M6 |
| FR-M05 | Bisnis demo beserta seluruh datanya dihapus otomatis 7 hari setelah dibuat oleh tugas terjadwal. Masa aktif/tenggang tidak berlaku untuk bisnis demo. | M6 |
| FR-M06 | Pembuatan demo dibatasi (rate limit) per alamat IP (default: maksimal 3 demo per hari per IP) untuk mencegah penyalahgunaan. | M6 |

### 5.F Loyalti (Stempel) & Promo

| ID | Kebutuhan | Milestone |
|---|---|---|
| FR-L01 | Owner mengatur program stempel per bisnis: aktif/nonaktif, jumlah stempel yang dibutuhkan (N), jenis layanan yang digratiskan (satu layanan master satuan-kg), dan berat maksimal gratis (kg). Default row bisnis baru: nonaktif, N=10, layanan hadiah dan berat maks null. Saat diaktifkan wajib N≥1, layanan master satuan kg milik bisnis, dan berat maks >0; saat nonaktif konfigurasi hadiah boleh belum lengkap. | M4 |
| FR-L02 | Perolehan stempel: setiap transaksi yang mencapai status bayar **`LUNAS`** dan tidak dibatalkan menambah 1 stempel ke pelanggan, berlaku lintas cabang dalam satu bisnis. Pembatalan transaksi yang sudah memberi stempel mencabut stempel tersebut. | M4 |
| FR-L03 | Penukaran stempel: jika stempel pelanggan ≥ N, saat membuat transaksi sistem menawarkan penukaran (maksimal satu penukaran per transaksi). Penukaran memotong harga item layanan yang ditentukan owner sebesar `harga × min(berat, berat_maks_gratis)`; kelebihan berat di atas batas tetap dibayar pelanggan; sisa kuota di bawah batas tidak disimpan. Penukaran mengurangi stempel pelanggan sebanyak N, dan transaksi penukaran **tidak** menambah stempel baru. | M4 |
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
| FR-N04 | Pengendalian biaya WA: (a) saklar per jenis peristiwa (FR-O14) menentukan peristiwa mana yang memakai WA; (b) **batas bulanan** — jika jumlah pesan WA bulan berjalan mencapai batas yang diatur owner, pengiriman WA berikutnya otomatis jatuh ke email saja dan hal ini dicatat di log; (c) penghitung pesan WA bulan berjalan ditampilkan ke owner. | M3 |
| FR-N05 | Semua upaya notifikasi dicatat dalam `notification_logs` (kanal, tujuan, tipe, nomor pengingat bila otomatis, waktu, status). Pembukaan `wa.me` manual berstatus `dibuka_manual` dan tidak dianggap berhasil terkirim; log otomatis per kanal menjadi sumber idempotensi. Log terlihat di detail transaksi. | M3 |
| FR-N06 | Kegagalan kirim notifikasi tidak boleh menggagalkan perubahan status: pengiriman dilakukan asinkron lewat antrean (queue) berbasis database + penjadwal (cron) harian untuk pengingat. Tidak dibutuhkan Redis atau perangkat lunak tambahan. | M3 |

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
| FR-W02 | Service worker sederhana: cache aset statis (network-first untuk data; jangan cache respons API/data transaksi). Cache wajib diperbarui otomatis setiap deploy (cache busting) agar pengguna tidak terjebak di tampilan lama. Tidak ada fungsi offline-first — saat offline, tampilkan halaman "Anda sedang offline". | M6 |

---

## 6. Siklus Status Cucian

Status berjalan satu arah (kecuali pembatalan):

```
DITERIMA → DIPROSES → SIAP_DIAMBIL → SUDAH_DIAMBIL
     └──────────┴────────── DIBATALKAN (dengan alasan)
```

| Status | Arti | Pemicu |
|---|---|---|
| `DITERIMA` | Cucian diterima & tercatat | Otomatis saat transaksi dibuat |
| `DIPROSES` | Sedang dicuci/disetrika/dikerjakan | Admin |
| `SIAP_DIAMBIL` | Selesai, menunggu diambil | Admin — memicu notifikasi (FR-N01) |
| `SUDAH_DIAMBIL` | Sudah diambil pelanggan | Admin — wajib `LUNAS` terlebih dahulu (FR-A07) |
| `DIBATALKAN` | Transaksi dibatalkan | Admin/Owner, wajib isi alasan |

Aturan: status tidak boleh melompat mundur; setiap perubahan dicatat di riwayat status beserta waktu dan pengguna yang mengubah. Waktu perubahan ke `SIAP_DIAMBIL` dan `SUDAH_DIAMBIL` disimpan di kolom khusus untuk keperluan pengingat dan laporan. Empat tahap ini final — jangan menambah tahap antara (mis. "Dicuci"/"Disetrika" terpisah).

---

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
potongan_promo    = persen × promo_eligible_base, atau min(nilai_nominal, promo_eligible_base) [jika promo sah]
total_akhir       = subtotal − potongan_stempel − potongan_promo   (minimal 0)
```

Urutan validasi: hitung subtotal dan potongan stempel (maksimal nilai item hadiah), tentukan `promo_eligible_base`, lalu uji status/periode/cabang promo dan `promo_eligible_base >= minimal_total` bila minimum diisi. Baru hitung potongan promo. Potongan stempel dan persen dibulatkan ke rupiah terdekat (0,5 ke atas) dan dibatasi masing-masing pada nilai item hadiah dan `promo_eligible_base`. Nilai yang ditagih adalah `total_akhir ≥ 0`; `promo_eligible_base` merupakan nilai turunan, bukan kolom wajib.

### 7.3 Estimasi selesai

`estimasi_selesai = waktu_masuk + durasi_terlama` di antara semua layanan dalam transaksi (durasi diatur owner per layanan). Admin dapat mengubah estimasi secara manual **selama `DITERIMA`** bila perlu (mis. antrean penuh); sejak `DIPROSES` estimasi terkunci. Transaksi yang melewati estimasi tanpa mencapai "Siap Diambil" masuk daftar "Terlambat" (FR-A11).

### 7.4 Aturan pembayaran & uang muka (DP)

1. Pembayaran dicatat sebagai daftar catatan pembayaran per transaksi (jumlah, metode tunai/transfer, waktu, pencatat). Satu transaksi bisa punya beberapa catatan (mis. DP saat masuk + pelunasan saat ambil).
2. Status bayar diturunkan otomatis: bila `total_akhir = 0`, `LUNAS` tanpa baris pembayaran Rp0; bila total akhir > 0 dan terbayar = 0, `BELUM_BAYAR`; bila 0 < terbayar < total akhir, `DP`; bila terbayar = total akhir, `LUNAS`. Total terbayar tidak boleh melebihi total akhir dan baris pembayaran harus > 0.
3. DP hanya bisa dicatat jika saklar "Terima uang muka" bisnis aktif (FR-O07). Mematikan saklar hanya memengaruhi transaksi baru; transaksi ber-DP yang berjalan tetap bisa dilunasi.
4. **Bisnis tidak menerima hutang:** pelunasan wajib tercatat paling lambat saat pengambilan; transaksi `SUDAH_DIAMBIL` selalu `LUNAS`.
5. Catatan pembayaran bersifat permanen — tidak dapat diubah atau dihapus. Kesalahan pencatatan dipulihkan lewat aturan Bagian 7.6.
6. Resi, halaman status, dan notifikasi menampilkan jumlah terbayar dan sisa tagihan bila belum lunas.
7. Stempel loyalti diberikan pada saat transaksi mencapai `LUNAS` (FR-L02), termasuk transaksi Rp0 tanpa pembayaran, kecuali transaksi yang memakai penukaran stempel.
8. Pencatatan pembayaran wajib atomik: kunci baris transaksi (`SELECT ... FOR UPDATE`), hitung ulang total pembayaran dan sisa, validasi nominal, sisipkan pembayaran, turunkan status bayar, proses stempel, lalu commit. Dua pembayaran paralel tidak boleh bersama-sama melampaui sisa.

### 7.5 Definisi pendapatan (untuk laporan)

- **Pendapatan** = jumlah seluruh **catatan pembayaran yang diterima** pada periode laporan, berdasarkan tanggal pembayaran — bukan per transaksi. Dengan demikian DP yang diterima bulan ini dan pelunasannya bulan depan tercatat di bulannya masing-masing.
- Pembayaran milik transaksi `DIBATALKAN` **dikeluarkan** dari laporan pendapatan (pengembalian dana terjadi di luar aplikasi dan tidak dicatat).
- **Tagihan berjalan** = jumlah sisa tagihan (total akhir − terbayar) dari transaksi aktif berstatus `BELUM_BAYAR`/`DP`.

### 7.6 Aturan pemulihan kesalahan (final)

Selama `DITERIMA`, edit field harga hanya boleh sebelum ada pembayaran **dan** sebelum ada penukaran stempel; pemeriksaan ini dilakukan ulang secara atomik saat menyimpan. Catatan kondisi dan estimasi selesai dapat diubah selama `DITERIMA`. Seluruh field operasional terkunci sejak `DIPROSES` dan pembayaran permanen bagi semua peran; pembaruan `notification_email` melalui FR-C04 adalah perubahan preferensi notifikasi yang sempit, bukan izin mengedit transaksi operasional, dan hanya sampai sebelum `SIAP_DIAMBIL` saat tenant dapat menulis. Kesalahan setelah kunci harga/status dipulihkan hanya lewat **batalkan (wajib alasan) → buat transaksi baru**. Pembatalan mengeluarkan pembayaran dari laporan, menambah `pengembalian_penukaran` sebesar stempel yang dahulu ditukar, dan/atau `pencabutan_perolehan` untuk stempel yang pernah diperoleh; entry lama tetap ada. Jika pencabutan perolehan lama membuat saldo di bawah nol karena stempel sudah dipakai pada transaksi lain, saldo bertanda tetap dicatat dan penukaran baru ditolak sampai saldo cukup. Jangan membangun buka-kunci atau pembatalan pembayaran.

Penukaran stempel memakai transaksi DB dan lock baris customer (`SELECT ... FOR UPDATE`), memeriksa saldo ledger/cache sesudah lock, lalu menulis delta negatif aktual yang berlaku saat itu. Dua penukaran paralel tidak boleh sama-sama membelanjakan saldo yang sama.

### 7.7 Aturan resi terpisah

Satu resi memiliki tepat satu status. Cucian dengan waktu pengambilan berbeda (mis. express + reguler) dicatat sebagai resi terpisah. Tidak ada status per item.

---

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
| Kirim manual oleh admin | Link `wa.me` berisi teks siap kirim, dan/atau kirim ulang email | Kapan pun (FR-A17, FR-A09) |

Ketentuan teknis:

- Notifikasi instan dikirim lewat antrean (queue) dengan **driver database** — antrean disimpan di tabel MySQL biasa; tidak dibutuhkan Redis atau perangkat lunak tambahan. Beban ini ringan untuk volume bisnis laundry.
- Pengingat "belum diambil" dijalankan oleh **penjadwal (cron) sekali sehari** yang memindai transaksi `SIAP_DIAMBIL` dan mengirim sesuai aturan.
- Pembagian tanggung jawab konfigurasi: **developer memegang yang teknis** (penyedia WA, token, nomor pengirim, email pengirim, SMTP — FR-D06); **owner memegang yang perilaku** (pengingat, saklar per peristiwa, batas WA bulanan — FR-O14).
- Nomor HP disimpan dalam format internasional (mis. `62812xxxxxxx`) agar kompatibel dengan `wa.me` dan API WhatsApp.
- Di mode demo, seluruh pengiriman ditekan — hanya dicatat di log (FR-M04). Di mode baca-saja, notifikasi otomatis berhenti (FR-D05).
- Email tujuan setiap transaksi berasal dari `transactions.notification_email`: disalin dari `customers.email` saat transaksi dibuat; form FR-C04 memperbarui email transaksi itu dan email customer secara atomik. Perubahan customer pada masa depan tidak mengubah snapshot transaksi lama.
- Setiap notifikasi otomatis memiliki identitas logis per transaksi, tipe, kanal, dan nomor pengingat (untuk `SIAP_DIAMBIL` gunakan nomor 0). `notification_logs` menjadi penjaga idempotensi per kanal; retry memakai identitas yang sama. Pembukaan `wa.me` manual hanya dicatat `dibuka_manual`, bukan `berhasil`; status sukses WA otomatis hanya dari respons penyedia.
- Di mode baca-saja, link `wa.me` manual masih dapat dibuka dari data yang sudah tampil, tetapi tidak membuat log baru; pengiriman ulang email manual adalah tulis bisnis dan ditolak. Pada mode tulis, pembukaan `wa.me` dicatat mulai M3 ketika `notification_logs` tersedia.

---

## 10. Kebutuhan Data (Model Data Tingkat Tinggi)

Ini gambaran entitas utama — detail kolom final ditentukan saat implementasi, tetapi jangan menghilangkan informasi di bawah ini. **Semua data operasional terikat ke satu bisnis (tenant) dan wajib difilter berdasarkan bisnis pada setiap kueri.**

- **businesses** (tenant) — nama bisnis, status aktif, masa aktif (tanggal), penanda demo (`is_demo`), waktu kedaluwarsa demo; konfigurasi teknis (penyedia WA, kredensial, nomor pengirim, saklar WA global; nama & alamat email pengirim, SMTP opsional).
- **users** — nama, email, password (hash), peran (`developer` / `owner` / `admin`), bisnis (kecuali developer), cabang penugasan (khusus admin), status aktif, penanda wajib ganti password.
- **branches** — bisnis, nama tempat, alamat, no. telepon, status aktif.
- **master_services** — bisnis; nama, satuan (`kg`/`item`), harga, durasi (jam), berat minimum (opsional), status aktif.
- **services** — bisnis dan cabang; atribut sama dengan master; diisi manual atau lewat mekanisme salin/sebarkan dari master (FR-O05).
- **customers** — bisnis, nama, no. HP (unik per bisnis), email (opsional), jumlah stempel. Berlaku lintas cabang dalam satu bisnis.
- **transactions** — bisnis, kode resi (unik global), cabang, pelanggan, pembuat, `notification_email` snapshot, status, waktu masuk, estimasi selesai, waktu siap/diambil, subtotal, potongan stempel, promo terpakai (referensi + snapshot nama/tipe/nilai), potongan promo, total akhir, status bayar turunan, catatan kondisi, alasan pembatalan.
- **payments** — bisnis, transaksi, jumlah, metode (`tunai`/`transfer`), waktu, pencatat. Tidak dapat diubah/dihapus.
- **transaction_items** — transaksi, layanan, nama/satuan/harga snapshot, berat kg / jumlah unit, perkiraan jumlah baju (opsional), penanda item gratis-stempel, subtotal final. Tenant diturunkan dari transaksi.
- **promos** — bisnis, nama, tipe (`persen`/`nominal`), nilai, minimal total (opsional), tanggal mulai, tanggal selesai, cabang berlaku (semua/sebagian), status aktif.
- **loyalty_settings** — bisnis, aktif/nonaktif, N stempel, layanan gratis (referensi ke layanan master), berat maksimal gratis.
- **loyalty_histories** — bisnis, pelanggan, jenis empat peristiwa FR-L04, delta stempel aktual bertanda, transaksi terkait, waktu.
- **status_histories** — bisnis, transaksi, status, waktu, pengguna pengubah.
- **notification_logs** — bisnis, transaksi, kanal (`email`/`whatsapp`/`whatsapp_manual`), tipe (`siap_diambil`/`pengingat`/`resi`), nomor pengingat/identitas logis, tujuan, status kirim termasuk `dibuka_manual`, waktu. Penghitung WA bulanan hanya dari WA otomatis yang `berhasil`.
- **business_settings** — pengaturan perilaku per bisnis: saklar DP, konfigurasi pengingat (N/M/K), saklar WA per peristiwa, batas WA bulanan.
- **audit_logs** — bisnis (nullable hanya untuk aksi developer tanpa tenant tertentu), pelaku, aksi, detail, waktu; aksi berisiko tetap berjejak.

---

## 11. Teknologi

Stack berikut adalah keputusan, bukan sekadar rekomendasi:

1. **Backend:** Laravel 12 + MySQL. Manfaatkan fasilitas bawaan: autentikasi & otorisasi per peran, queue driver database, penjadwal cron, sistem notifikasi email, dan testing.
2. **Frontend panel (developer, owner, admin):** Inertia.js + React (TypeScript) + Tailwind CSS + shadcn/ui — dipilih agar antarmuka owner bisa didesain berkualitas konsumen (bukan tampilan panel admin generik), mengikuti aturan desain Bagian 12.2.
3. **Halaman publik (halaman depan, cek status, resi):** halaman ringan terpisah dari bundle panel — Blade polos atau React minimal — agar cepat dimuat di HP pelanggan dengan koneksi lambat.
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
8. Edit harga transaksi `DITERIMA` hanya sebelum pembayaran/penukaran; edit catatan/estimasi masih boleh saat `DITERIMA`. Field operasional terkunci sejak `DIPROSES` untuk semua peran, dengan pengecualian email notifikasi FR-C04 sebelum siap diambil; pemulihan kesalahan lewat batalkan + buat ulang.
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
3. **DP:** pada transaksi bertotal Rp74.500, DP Rp30.000 saat masuk membuat status bayar `DP` dan sisa Rp44.500 tampil di resi, halaman status, dan notifikasi; pelunasan Rp44.500 saat pengambilan mengubah status ke `LUNAS` dan membuka izin "Sudah Diambil"; jika DP diterima bulan Juli dan pelunasan bulan Agustus, laporan Juli mencatat Rp30.000 dan Agustus Rp44.500; mencatat pembayaran melebihi sisa tagihan ditolak; saat saklar DP dimatikan, transaksi baru tidak bisa dicatat DP tetapi transaksi ber-DP lama tetap bisa dilunasi.
4. **Kunci permanen:** pada `DITERIMA` tanpa pembayaran/penukaran, item dan promo boleh diedit. Begitu ada salah satunya, edit harga ditolak, termasuk contoh total awal Rp100.000, pembayaran Rp80.000, lalu upaya menurunkan total ke Rp60.000; catatan/estimasi masih dapat diedit. Sejak `DIPROSES`, semua edit ditolak; pembayaran tidak bisa diubah/dihapus; batalkan + buat ulang mengeluarkan transaksi batal dari pendapatan.
5. **Layanan master:** cabang dengan harga lokal berbeda pada layanan bernama sama akan mengikuti harga master setelah "Salin/Perbarui dari Master", dengan pratinjau tampil sebelum eksekusi; layanan khusus cabang tetap utuh; transaksi lama tidak berubah.
6. **Stempel:** dengan N=10 dan hadiah "Cuci+Setrika maks 3 kg" @Rp7.000, pelanggan bersaldo 10 membawa 5 kg mendapat potongan Rp21.000, membayar sisa, dan ledger mencatat −10; transaksi penukaran tidak menambah stempel. Saat transaksi ditukar dibatalkan, ledger menambah +10 tanpa menghapus entry −10. Saldo = `SUM(jumlah)`, termasuk setelah N diubah; dua penukaran paralel tidak menghabiskan saldo dua kali.
7. **Promo:** `minimal_total` diuji terhadap subtotal setelah potongan stempel; promo persen dan nominal dihitung dari basis yang sama, nominal dibatasi basis, total tidak negatif. Promo kedaluwarsa/di luar cabang/di bawah minimum ditolak; snapshot nama/tipe/nilai dan hasil potongan tetap utuh setelah master berubah.
8. **Notifikasi & batas WA:** tiap peristiwa otomatis dibedakan per kanal dan nomor pengingat; retry tidak mengirim ulang kanal yang sudah berhasil. Dengan batas WA bulanan 100, peristiwa ke-101 email saja dan WA tercatat `dilewati_batas`. `wa.me` manual tercatat `dibuka_manual`, bukan sukses terkirim.
9. **Masa aktif & baca-saja:** setelah tenggang, login dan ganti/reset password tetap dapat dilakukan; tulis bisnis ditolak server, notifikasi otomatis berhenti; `GET /t/{kode_resi}` tetap terbuka pada tenant baca-saja/nonaktif, sedangkan POST email ditolak.
10. **Isolasi:** owner/admin bisnis A tidak dapat melihat/mengubah data bisnis B, dan admin cabang 1 tidak dapat melihat/mengubah transaksi cabang 2, termasuk lewat manipulasi URL atau parameter.
11. **Demo:** klik "Coba Demo" menghasilkan lingkungan demo lengkap dan langsung masuk sebagai owner; tombol ganti peran berfungsi; tidak ada email/WA nyata terkirim; demo terhapus otomatis setelah 7 hari; pembuatan demo dari IP yang sama dibatasi.
12. **Resi & PWA:** hasil cetak thermal 58 mm terbaca rapi dengan QR terpindai dan mencantumkan DP/sisa bila ada; aplikasi bisa di-install ke home screen; setelah deploy, pengguna otomatis mendapat aset terbaru.
13. **Total Rp0 & pembayaran paralel:** total akhir Rp0 langsung `LUNAS` tanpa payment Rp0; dua pembayaran bersamaan terhadap sisa Rp50.000 tidak boleh menghasilkan total terbayar Rp100.000.
14. **Email transaksi:** email customer pada saat pembuatan disalin ke transaksi; pengisian dari halaman status memperbarui snapshot transaksi itu dan customer; perubahan customer sesudahnya tidak mengubah snapshot transaksi lama atau tujuan notifikasinya.
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
| Snapshot | Nilai (harga/promo) yang disalin ke transaksi saat dibuat, tidak ikut berubah jika data master diubah |
| Layanan satuan kg / item | Layanan yang dihitung per kilogram vs per buah |
| Mode demo | Tenant sementara berisi data contoh untuk calon pemilik laundry, kedaluwarsa 7 hari |
| PWA ringan | Aplikasi web yang bisa di-install ke home screen dengan cache aset, tanpa kemampuan offline-first |
| `wa.me` | Link resmi WhatsApp untuk membuka chat ke nomor tertentu tanpa API berbayar |

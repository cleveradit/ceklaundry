# User Stories & Kriteria Penerimaan — CekLaundry

> **Kedudukan dokumen:** turunan dari `prd.md`. Setiap story merujuk ID kebutuhan (`FR-…`) dan milestone (M1–M6). Kriteria penerimaan memakai format Given-When-Then dan menjadi dasar pengujian otomatis. Jika ada pertentangan, `prd.md` menang. Semua story wajib diselesaikan.

Peran dalam story: **Pelanggan** (tanpa login), **Admin** (pegawai cabang), **Owner** (pemilik bisnis), **Developer** (pengelola aplikasi), **Prospek** (calon pemilik laundry, mode demo).

---

## Epic M1 — Fondasi & Tenant

### US-101 — Login & wajib ganti password
FR: FR-A01, FR-O01, FR-D01, FR-D02 · M1

Sebagai pengguna ber-akun (developer/owner/admin), saya ingin masuk dengan email + password dan dipaksa mengganti password awal, agar akun saya aman sejak awal.

1. **Given** akun aktif dengan kredensial benar, **When** login, **Then** pengguna diarahkan ke panel sesuai perannya.
2. **Given** akun berpenanda `must_change_password`, **When** login berhasil, **Then** pengguna dipaksa ke halaman ganti password dan tidak bisa membuka halaman lain sebelum menggantinya.
3. **Given** akun dinonaktifkan (`is_active = false`) atau bisnisnya dinonaktifkan developer, **When** login, **Then** akses ditolak dengan pesan yang jelas.
4. **Given** pengguna lupa password, **When** meminta reset via email, **Then** tautan reset terkirim dan dapat dipakai sekali.

### US-102 — Developer mendaftarkan bisnis baru
FR: FR-D02 · M1

Sebagai developer, saya ingin mendaftarkan bisnis laundry baru beserta akun owner-nya, agar pelanggan baru aplikasi bisa langsung mulai.

1. **Given** form bisnis baru terisi (nama bisnis, nama/email/password awal owner, masa aktif), **When** disimpan, **Then** tercipta satu `business`, satu user `owner` berpenanda wajib ganti password, satu baris `business_settings` dan `loyalty_settings` default.
2. **Given** email owner sudah dipakai user lain, **When** disimpan, **Then** ditolak dengan pesan validasi.

### US-103 — Developer mengelola masa aktif & daftar bisnis
FR: FR-D03, FR-D04 · M1

Sebagai developer, saya ingin melihat semua bisnis beserta statusnya dan mengubah masa aktifnya, agar aktivasi langganan manual mudah dikelola.

1. **Given** panel developer dibuka, **When** melihat daftar bisnis, **Then** tampil nama, status (aktif/tenggang/baca-saja/nonaktif/demo), masa aktif, jumlah cabang, dan jumlah transaksi 30 hari terakhir — tanpa akses ke data transaksi/pelanggan mana pun.
2. **Given** sebuah bisnis, **When** developer mengubah `active_until`, menonaktifkan/mengaktifkan, atau mereset password owner, **Then** perubahan berlaku seketika dan tercatat di `audit_logs`.

### US-104 — Siklus masa aktif: peringatan → tenggang → baca-saja
FR: FR-D05 · M1

Sebagai owner, saya ingin diberi peringatan bertahap saat masa aktif menipis, agar operasional tidak terputus mendadak.

1. **Given** masa aktif habis ≤ 7 hari lagi, **When** owner membuka panel, **Then** banner peringatan tampil.
2. **Given** masa aktif habis < 7 hari yang lalu (tenggang), **When** owner/admin bekerja, **Then** seluruh fungsi berjalan normal dengan banner mencolok.
3. **Given** tenggang telah lewat (baca-saja), **When** owner/admin login, **Then** login berhasil, semua data terbaca, namun setiap request tulis (POST/PUT/PATCH/DELETE selain logout) ditolak server dengan status 423 dan tombol aksi di UI nonaktif.
4. **Given** bisnis dalam mode baca-saja, **When** penjadwal notifikasi berjalan, **Then** tidak ada notifikasi otomatis terkirim untuk bisnis itu.
5. **Given** bisnis dalam mode baca-saja atau nonaktif, **When** pelanggan membuka `/t/{kode_resi}` transaksinya, **Then** halaman status tetap tampil normal.

### US-105 — Owner mengelola cabang
FR: FR-O02 · M1

Sebagai owner, saya ingin menambah dan mengubah cabang, agar data cabang tampil benar di resi dan halaman status.

1. **Given** form cabang terisi (nama, alamat, telepon), **When** disimpan, **Then** cabang tercipta dan datanya dipakai di resi & halaman status.
2. **Given** cabang masih memiliki transaksi aktif (belum diambil & belum dibatalkan), **When** owner mencoba menonaktifkannya, **Then** ditolak dengan pesan yang menjelaskan alasannya.
3. **Given** cabang nonaktif, **When** owner membuka laporan, **Then** riwayat transaksi cabang itu tetap tampil.

### US-106 — Owner mengelola akun admin
FR: FR-O03 · M1

Sebagai owner, saya ingin mendaftarkan admin dan menugaskannya ke satu cabang, agar tiap cabang punya operator.

1. **Given** form admin terisi (nama, email, password awal, cabang), **When** disimpan, **Then** admin tercipta dengan penanda wajib ganti password dan terikat ke tepat satu cabang.
2. **Given** satu cabang, **When** owner menambahkan admin kedua ke cabang yang sama, **Then** diperbolehkan (satu cabang boleh banyak admin).
3. **Given** admin lupa password, **When** owner menekan "Reset password", **Then** password baru/tautan reset dibuat dan aksi tercatat di `audit_logs`.

### US-107 — Owner mengelola Layanan Master
FR: FR-O04 · M1

Sebagai owner, saya ingin mengelola daftar layanan di satu tempat, agar tidak mengatur ulang dari nol untuk tiap cabang.

1. **Given** form layanan master terisi (nama, satuan kg/item, harga, durasi jam, berat minimum opsional), **When** disimpan, **Then** layanan master tercipta; nama unik per bisnis.
2. **Given** layanan express diinginkan, **When** owner membuat layanan berdurasi lebih pendek dan berharga lebih tinggi, **Then** tidak ada fitur khusus lain yang diperlukan.

### US-108 — Sinkronisasi layanan master ke cabang
FR: FR-O05 · M1

Sebagai owner, saya ingin menyalin/memperbarui layanan cabang dari master dengan aturan timpa yang jelas, agar mengubah harga banyak cabang cukup sekali kerja.

1. **Given** cabang tanpa layanan, **When** "Salin/Perbarui dari Master" dijalankan, **Then** seluruh layanan master aktif tersalin ke cabang.
2. **Given** cabang memiliki layanan bernama sama dengan master namun harga berbeda, **When** sinkronisasi dijalankan, **Then** layanan cabang itu ditimpa mengikuti master (harga, durasi, berat minimum, satuan).
3. **Given** cabang memiliki layanan khusus yang tidak ada di master, **When** sinkronisasi dijalankan, **Then** layanan khusus itu tidak disentuh.
4. **Given** sinkronisasi akan dijalankan, **When** owner menekan tombolnya, **Then** pratinjau tampil lebih dulu ("X diperbarui, Y ditambahkan") dan eksekusi butuh konfirmasi.
5. **Given** halaman master, **When** owner memakai "Sebarkan ke Cabang" dengan mencentang beberapa cabang, **Then** aturan 1–4 berlaku untuk setiap cabang tercentang.
6. **Given** transaksi lama dengan harga snapshot, **When** sinkronisasi mengubah harga cabang, **Then** transaksi lama tidak berubah.

### US-109 — Isolasi tenant & cabang
FR: Bagian 3.2 `prd.md`; NFR-ISO · M1

Sebagai pemilik data, saya ingin data bisnis dan cabang terisolasi mutlak, agar tidak ada kebocoran antarbisnis.

1. **Given** user bisnis A, **When** mengakses URL/ID resource milik bisnis B (transaksi, pelanggan, laporan, pengaturan), **Then** respons 404 — bukan 403 — tanpa membocorkan keberadaan data.
2. **Given** admin cabang X, **When** mengakses transaksi/dashboard cabang Y bisnis yang sama, **Then** respons 404/daftar kosong.
3. **Given** developer, **When** mencoba membuka data transaksi/pelanggan bisnis mana pun, **Then** akses ditolak oleh policy.

---

## Epic M2 — Operasional Inti

### US-201 — Halaman depan & pencarian kode resi
FR: FR-C01, FR-C05 · M2

Sebagai pelanggan, saya ingin langsung mengecek status dari halaman depan, agar tidak perlu bertanya ke laundry.

1. **Given** halaman depan dibuka, **When** halaman tampil, **Then** kolom input kode resi + tombol "Cek Status" adalah elemen utama, dan tombol "Coba Demo" tersedia.
2. **Given** kode resi valid dimasukkan, **When** "Cek Status" ditekan, **Then** pelanggan diarahkan ke `/t/{kode_resi}`.
3. **Given** kode tidak terdaftar, **When** dicari, **Then** tampil pesan "Kode resi tidak ditemukan, periksa kembali resi Anda" tanpa informasi lain.

### US-202 — Halaman status publik
FR: FR-C02, FR-C03, FR-C06 · M2

Sebagai pelanggan, saya ingin melihat detail dan progres cucian saya di satu halaman, agar tahu kapan harus mengambil dan berapa sisa tagihan saya.

1. **Given** transaksi berkode `K7F3XA`, **When** `/t/K7F3XA` dibuka (termasuk via scan QR), **Then** tampil: timeline status + waktu tiap tahap, rincian item, potongan (jika ada), total akhir, terbayar & sisa tagihan, status bayar, estimasi selesai, catatan kondisi, dan info cabang dengan telepon yang bisa diklik.
2. **Given** halaman status publik, **When** data pelanggan ditampilkan, **Then** nama dan no. HP tersamar sebagian (mis. "Rad*** — 0812***678").
3. **Given** pengecekan kode berulang cepat dari satu IP, **When** melewati ambang rate limit, **Then** permintaan berikutnya ditolak sementara (NFR-SEC).

### US-203 — Membuat transaksi baru
FR: FR-A02, FR-A03, FR-A04; aturan 7.1 & 7.7 · M2

Sebagai admin, saya ingin mencatat cucian masuk beserta layanannya dalam waktu kurang dari satu menit, agar antrean pelanggan cepat terlayani.

1. **Given** pelanggan lama, **When** admin mengetik no. HP/nama, **Then** data pelanggan ditemukan tanpa input ulang; **Given** pelanggan baru, **Then** cukup nama + no. HP (email opsional).
2. **Given** item "Cuci+Setrika 3,5 kg @Rp7.000" dan "Bed Cover 2 item @Rp25.000", **When** transaksi disimpan, **Then** subtotal Rp24.500 dan Rp50.000, total Rp74.500, dan nama+harga layanan ter-snapshot pada item.
3. **Given** layanan berberat minimum 3 kg, **When** berat diisi 2 kg, **Then** subtotal dihitung memakai 3 kg.
4. **Given** kolom catatan kondisi diisi "noda di kerah", **When** disimpan, **Then** catatan tampil di resi dan halaman status.
5. **Given** transaksi tersimpan, **When** kode resi dibuat, **Then** kode 6 karakter tanpa O/0/I/1/L dan unik global.
6. **Given** pelanggan membawa cucian express dan reguler yang ingin diambil terpisah, **When** admin mencatat, **Then** dicatat sebagai dua resi terpisah (panduan 7.7 tampil sebagai petunjuk di form).

### US-204 — Estimasi selesai otomatis
FR: aturan 7.3 · M2

Sebagai admin, saya ingin estimasi selesai terisi otomatis namun bisa dikoreksi, agar akurat tanpa menghitung manual.

1. **Given** transaksi berisi layanan berdurasi 48 jam dan 6 jam, **When** disimpan pukul 08.00, **Then** estimasi = waktu masuk + 48 jam.
2. **Given** antrean sedang penuh, **When** admin mengubah estimasi manual, **Then** nilai manual yang berlaku dan tampil di resi/halaman status.

### US-205 — Memperbarui status cucian
FR: FR-A05; Bagian 6 · M2

Sebagai admin, saya ingin memperbarui status satu arah dengan jejak lengkap, agar progres bisa dipercaya.

1. **Given** transaksi `DITERIMA`, **When** admin mengubah ke `DIPROSES` lalu `SIAP_DIAMBIL`, **Then** tiap perubahan tercatat di riwayat (status, waktu, pengguna) dan `waktu_siap_diambil` terisi.
2. **Given** transaksi `SIAP_DIAMBIL`, **When** admin mencoba mengembalikannya ke `DIPROSES` atau melompat dari `DITERIMA` langsung ke `SIAP_DIAMBIL`... **Then** transisi mundur ditolak; transisi maju hanya sah ke tahap tepat berikutnya.
3. **Given** transaksi apa pun sebelum `SUDAH_DIAMBIL`, **When** dibatalkan tanpa alasan, **Then** ditolak; dengan alasan, status menjadi `DIBATALKAN`.

### US-206 — Mencatat pembayaran & uang muka (DP)
FR: FR-A06, FR-O07; aturan 7.4 · M2

Sebagai admin, saya ingin mencatat pembayaran penuh maupun DP sebagai catatan permanen, agar keuangan akurat dan tidak bisa diutak-atik.

1. **Given** saklar DP bisnis aktif dan transaksi bertotal Rp74.500, **When** admin mencatat DP Rp30.000 (tunai), **Then** status bayar menjadi `DP`, sisa Rp44.500 tampil di resi/halaman status.
2. **Given** transaksi ber-DP tersebut, **When** pelunasan Rp44.500 dicatat saat pengambilan, **Then** status bayar `LUNAS` dan penyerahan diizinkan.
3. **Given** sisa tagihan Rp44.500, **When** admin mencoba mencatat Rp50.000, **Then** ditolak (melebihi sisa).
4. **Given** saklar DP dimatikan owner, **When** admin membuat transaksi baru, **Then** pilihan pembayaran hanya lunas penuh atau belum bayar; **Given** transaksi ber-DP lama, **Then** tetap bisa dilunasi.
5. **Given** catatan pembayaran tersimpan, **When** siapa pun mencoba mengubah/menghapusnya, **Then** tidak ada jalur untuk itu (tidak ada endpoint).

### US-207 — Larangan penyerahan sebelum lunas
FR: FR-A07 · M2

Sebagai owner, saya ingin cucian hanya bisa diserahkan setelah lunas, agar bisnis tidak menanggung hutang pelanggan.

1. **Given** transaksi `SIAP_DIAMBIL` berstatus bayar `BELUM_BAYAR` atau `DP`, **When** admin mencoba mengubah ke `SUDAH_DIAMBIL`, **Then** ditolak dengan pesan sisa tagihan.
2. **Given** pelunasan dicatat, **When** status diubah ke `SUDAH_DIAMBIL`, **Then** berhasil dan `waktu_diambil` terisi.

### US-208 — Kunci transaksi & pemulihan lewat pembatalan
FR: FR-A15, FR-A16; aturan 7.6 · M2

Sebagai owner, saya ingin transaksi terkunci sejak diproses dan kesalahan dipulihkan lewat pembatalan berjejak, agar tidak ada manipulasi data.

1. **Given** transaksi `DITERIMA`, **When** admin mengedit item/berat/catatan, **Then** diperbolehkan dan total dihitung ulang.
2. **Given** transaksi `DIPROSES` atau setelahnya, **When** admin **atau owner** mencoba mengedit, **Then** ditolak — tidak ada fitur buka-kunci.
3. **Given** salah timbang ketahuan saat `DIPROSES`, **When** admin membatalkan (dengan alasan) lalu membuat transaksi baru yang benar, **Then** transaksi lama keluar dari pendapatan, pembayarannya dikecualikan dari laporan, stempelnya (bila ada) dicabut, dan transaksi baru berjalan normal.

### US-209 — Resi, QR, dan cetak thermal
FR: FR-A08, FR-R01–R04 · M2

Sebagai admin, saya ingin mencetak resi 58 mm ber-QR, agar pelanggan bisa cek status dengan sekali scan.

1. **Given** transaksi tersimpan, **When** resi dicetak, **Then** memuat: identitas cabang, kode resi, tanggal masuk, pelanggan, rincian item + harga satuan + subtotal, potongan (jika ada), total akhir, terbayar & sisa, status bayar, estimasi selesai, catatan kondisi, QR berisi `/t/{kode}`, dan catatan kaki link cek status.
2. **Given** printer thermal 58 mm, **When** dialog print browser dipakai, **Then** tata letak rapi pada lebar ±48 mm dan QR terpindai.
3. **Given** link halaman status dibagikan, **When** dibuka, **Then** berfungsi sebagai resi bentuk web.

### US-210 — Kirim resi via WhatsApp manual
FR: FR-A09 · M2

Sebagai admin, saya ingin mengirim ringkasan resi lewat `wa.me` tanpa biaya API, agar pelanggan langsung pegang link statusnya.

1. **Given** transaksi tersimpan dan no. HP pelanggan `62…`, **When** "Kirim resi via WhatsApp" ditekan, **Then** terbuka `wa.me/{no_hp}` berisi teks ringkasan (kode, total, sisa tagihan, estimasi, link `/t/{kode}`).

### US-211 — Dashboard admin
FR: FR-A10, FR-A11 · M2

Sebagai admin, saya ingin melihat pekerjaan hari ini, cucian menumpuk, dan yang terlambat dalam satu layar, agar tahu prioritas.

1. **Given** dashboard dibuka, **When** data tampil, **Then** ada: transaksi hari ini, transaksi aktif per status, daftar "Siap Diambil — belum diambil" terurut umur menunggu (hari), dan daftar "Terlambat" (melewati estimasi, belum siap).
2. **Given** admin cabang X, **When** dashboard tampil, **Then** hanya data cabang X.

### US-212 — Pencarian transaksi
FR: FR-A12 · M2

Sebagai admin, saya ingin mencari transaksi via kode/nama/no. HP, agar pelanggan yang kehilangan resi tetap terlayani.

1. **Given** kata kunci kode resi, nama, atau no. HP, **When** pencarian dijalankan, **Then** transaksi cocok di cabang admin tampil; kombinasi dengan US-210 memungkinkan kirim ulang link status.

### US-213 — Kelola data pelanggan
FR: FR-A13 · M2

Sebagai admin, saya ingin menambah/mengedit pelanggan dengan no. HP sebagai identitas unik, agar riwayat pelanggan tidak terputus.

1. **Given** no. HP yang sudah terdaftar di bisnis, **When** admin menambah pelanggan baru dengan no. HP sama, **Then** ditolak (unik per bisnis).
2. **Given** dua pelanggan berbeda bernama sama dengan no. HP berbeda, **When** keduanya disimpan, **Then** diperbolehkan sebagai dua pelanggan.
3. **Given** pelanggan lama ganti nomor, **When** admin mengedit no. HP-nya, **Then** seluruh riwayat transaksi & stempel tetap melekat (terikat ID internal).

### US-214 — Gabung pelanggan duplikat
FR: FR-A14 · M2

Sebagai admin, saya ingin menggabungkan pelanggan ganda, agar stempel dan riwayat tidak terpecah.

1. **Given** pelanggan sumber & tujuan dipilih, **When** penggabungan dikonfirmasi, **Then** seluruh transaksi dan stempel pindah ke tujuan, data sumber terhapus, dan aksi tercatat di `audit_logs`.
2. **Given** dialog penggabungan, **When** belum dikonfirmasi eksplisit, **Then** tidak ada perubahan data.

### US-215 — Owner mengerjakan operasional lintas cabang
FR: FR-O06 · M2

Sebagai owner yang turun tangan sendiri, saya ingin memakai seluruh fungsi admin di semua cabang saya, agar bisa menggantikan pegawai kapan pun.

1. **Given** owner membuka panel operasional, **When** memilih salah satu cabangnya, **Then** seluruh kemampuan US-203–US-214 tersedia untuk cabang itu.
2. **Given** owner, **When** membuat transaksi, **Then** tercatat sebagai pembuat transaksi tersebut.

---

## Epic M3 — Notifikasi

### US-301 — Email otomatis "Siap Diambil"
FR: FR-N01 · M3

Sebagai pelanggan, saya ingin diberi tahu lewat email saat cucian selesai, agar tidak datang sia-sia.

1. **Given** transaksi berpelanggan ber-email, **When** status menjadi `SIAP_DIAMBIL`, **Then** tepat satu email terkirim (info laundry, kode, total, sisa tagihan bila ada, link status) dan `notified_ready_at` terisi.
2. **Given** status diubah bolak-balik oleh sistem/ulang render, **When** peristiwa terpicu lagi, **Then** email tidak terkirim dua kali.
3. **Given** pelanggan tanpa email, **When** status menjadi `SIAP_DIAMBIL`, **Then** tidak ada email dan tidak ada error.

### US-302 — Pengingat otomatis cucian belum diambil
FR: FR-N02 · M3

Sebagai owner, saya ingin pelanggan diingatkan berkala, agar cucian selesai tidak menumpuk.

1. **Given** pengaturan default N=2, M=2, K=3 dan transaksi `SIAP_DIAMBIL` sejak 2 hari lalu, **When** penjadwal harian berjalan, **Then** pengingat pertama terkirim dan `reminder_count` = 1.
2. **Given** pengingat sudah 3 kali, **When** penjadwal berjalan lagi, **Then** tidak ada pengingat tambahan.
3. **Given** transaksi diambil (`SUDAH_DIAMBIL`), **When** penjadwal berjalan, **Then** transaksi itu tidak menerima pengingat.
4. **Given** owner mengubah N/M/K, **When** penjadwal berjalan, **Then** aturan baru dipakai.

### US-303 — Pelanggan mendaftarkan email dari halaman status
FR: FR-C04 · M3

Sebagai pelanggan, saya ingin memasukkan email saya di halaman status, agar dapat kabar saat cucian selesai.

1. **Given** transaksi belum `SIAP_DIAMBIL` dan pelanggan belum ber-email, **When** email valid dikirim lewat form, **Then** email tersimpan ke transaksi & data pelanggan dan dipakai pada US-301.
2. **Given** format email tidak valid, **When** dikirim, **Then** ditolak dengan pesan validasi.

### US-304 — Ingatkan pelanggan secara manual
FR: FR-A17 · M3

Sebagai admin, saya ingin tombol pengingat manual, agar bisa menindak cucian menumpuk kapan saja.

1. **Given** transaksi `SIAP_DIAMBIL`, **When** "Ingatkan pelanggan" ditekan, **Then** email pengingat terkirim ulang (bila ber-email) dan/atau `wa.me` terbuka berisi teks pengingat; aksi tercatat di log notifikasi.

### US-305 — Konfigurasi teknis per bisnis (developer)
FR: FR-D06 · M3

Sebagai developer, saya ingin memegang seluruh konfigurasi teknis WA & email per bisnis, agar owner tidak pernah menyentuh hal teknis.

1. **Given** panel developer per bisnis, **When** konfigurasi diisi (penyedia WA, token, nomor pengirim; nama & alamat pengirim email; SMTP opsional), **Then** tersimpan terenkripsi dan hanya terlihat oleh developer.
2. **Given** WA belum dikonfigurasi/dinonaktifkan, **When** aplikasi berjalan, **Then** seluruh fungsi tetap utuh via email + `wa.me` manual.

### US-306 — Pengaturan perilaku notifikasi (owner)
FR: FR-O14 · M3

Sebagai owner, saya ingin mengatur perilaku notifikasi tanpa istilah teknis, agar bisa mengendalikan pengalaman pelanggan dan biaya.

1. **Given** halaman pengaturan owner, **When** dibuka, **Then** tersedia: saklar pengingat + N/M/K, saklar WA per peristiwa (Siap Diambil / pengingat), batas WA bulanan, dan penghitung WA bulan berjalan — tanpa token/istilah teknis.

### US-307 — WhatsApp otomatis & kendali biaya
FR: FR-N03, FR-N04 · M3

Sebagai owner, saya ingin WA otomatis yang biayanya terkendali, agar pelanggan terlayani tanpa tagihan membengkak.

1. **Given** WA aktif (kredensial terpasang) dan saklar peristiwa "Siap Diambil" menyala, **When** transaksi menjadi `SIAP_DIAMBIL`, **Then** WA terkirim via adapter penyedia bisnis itu dan log `berhasil` tercatat.
2. **Given** saklar peristiwa pengingat mati, **When** pengingat jatuh tempo, **Then** hanya email terkirim.
3. **Given** batas bulanan 100 dan sudah 100 WA `berhasil` bulan ini, **When** peristiwa ke-101 terjadi, **Then** WA tidak dikirim, email tetap terkirim, log `dilewati_batas` tercatat.
4. **Given** bulan berganti, **When** peristiwa terjadi, **Then** penghitung mulai dari nol.

### US-308 — Log notifikasi & keandalan
FR: FR-N05, FR-N06 · M3

Sebagai admin, saya ingin melihat riwayat notifikasi per transaksi dan yakin kegagalan kirim tidak mengganggu operasional.

1. **Given** detail transaksi dibuka, **When** melihat bagian notifikasi, **Then** tampil semua log (kanal, tipe, tujuan, status, waktu).
2. **Given** penyedia WA/SMTP sedang gagal, **When** status diubah ke `SIAP_DIAMBIL`, **Then** perubahan status tetap sukses; job kirim dicoba ulang hingga 3 kali lalu tercatat `gagal`.

---

## Epic M4 — Loyalti & Promo

### US-401 — Pengaturan program stempel
FR: FR-L01, FR-O09 · M4

Sebagai owner, saya ingin mengatur program stempel, agar pelanggan terdorong kembali.

1. **Given** form pengaturan (aktif, N, layanan gratis satuan-kg dari master, berat maks), **When** disimpan, **Then** program berlaku untuk seluruh cabang bisnis.
2. **Given** program nonaktif, **When** transaksi berjalan, **Then** tidak ada perolehan/penawaran penukaran dan halaman status tidak menampilkan stempel.

### US-402 — Perolehan & pencabutan stempel
FR: FR-L02 · M4

Sebagai pelanggan, saya ingin stempel bertambah tiap transaksi lunas, agar hadiah gratis makin dekat.

1. **Given** program aktif, **When** transaksi mencapai `LUNAS` (dan tidak dibatalkan), **Then** stempel pelanggan +1 dan `loyalty_histories` mencatat perolehan — berlaku lintas cabang.
2. **Given** transaksi pemberi stempel dibatalkan, **When** pembatalan diproses, **Then** stempel tersebut dicabut dan tercatat.
3. **Given** transaksi hasil penukaran, **When** mencapai `LUNAS`, **Then** **tidak** menambah stempel.

### US-403 — Penukaran stempel
FR: FR-L03, FR-A18 · M4

Sebagai pelanggan setia, saya ingin menukar stempel dengan cucian gratis berbatas berat, agar loyalitas saya dihargai.

1. **Given** N=10, hadiah "Cuci+Setrika maks 3 kg" @Rp7.000, pelanggan berstempel 10 membawa 5 kg, **When** penukaran diterapkan, **Then** potongan Rp21.000, sisa 2 kg dibayar (Rp14.000), stempel berkurang 10, item bertanda hadiah.
2. **Given** pelanggan yang sama membawa 2 kg, **When** ditukar, **Then** potongan Rp14.000 dan sisa kuota 1 kg hangus.
3. **Given** stempel pelanggan < N, **When** transaksi dibuat, **Then** penawaran penukaran tidak muncul.
4. **Given** satu transaksi, **When** admin mencoba menukar dua kali, **Then** ditolak (maksimal satu penukaran per transaksi).

### US-404 — Riwayat stempel
FR: FR-L04 · M4

Sebagai owner, saya ingin melihat riwayat stempel tiap pelanggan, agar program bisa diaudit.

1. **Given** halaman pelanggan, **When** riwayat dibuka, **Then** tampil perolehan/penukaran/pencabutan beserta transaksi terkait dan waktunya, dan saldo = penjumlahan riwayat.

### US-405 — Stempel di halaman status
FR: FR-C07 · M4

Sebagai pelanggan, saya ingin melihat jumlah stempel saya saat cek status, agar tahu jarak ke hadiah.

1. **Given** program stempel aktif, **When** `/t/{kode}` dibuka, **Then** tampil "Stempel Anda: 7/10"; **Given** program nonaktif, **Then** bagian ini tidak tampil.

### US-406 — Kelola promo
FR: FR-P01, FR-O08 · M4

Sebagai owner, saya ingin membuat promo bernama dengan masa berlaku dan cakupan cabang, agar diskon terkontrol penuh olehku.

1. **Given** form promo (nama, tipe persen/nominal, nilai, minimal total opsional, periode, semua/sebagian cabang), **When** disimpan, **Then** promo tersedia bagi admin pada cabang & periode yang sesuai.

### US-407 — Menerapkan promo pada transaksi
FR: FR-P02, FR-P03; aturan 7.2 · M4

Sebagai admin, saya ingin memilih satu promo dari daftar yang sah, agar potongan konsisten dengan kebijakan owner.

1. **Given** promo aktif pada cabang & periode berlaku dan minimal total terpenuhi, **When** dipilih di transaksi, **Then** potongan dihitung sesuai 7.2 dan nama/tipe/nilai promo di-snapshot ke transaksi.
2. **Given** promo kedaluwarsa, di luar cabang, atau minimal tak terpenuhi, **When** admin memilih promo, **Then** promo itu tidak tersedia/ditolak validasi.
3. **Given** transaksi memakai penukaran stempel dan promo persen 10%, **When** total dihitung, **Then** persen diambil dari subtotal **setelah** potongan stempel, dibulatkan ke rupiah terdekat, dan total akhir tidak pernah negatif.
4. **Given** promo diubah/dihapus owner, **When** transaksi lama dilihat, **Then** angka transaksi lama tidak berubah.

---

## Epic M5 — Laporan Owner

### US-501 — Riwayat transaksi & filter
FR: FR-O10 · M5

Sebagai owner, saya ingin menelusuri semua transaksi dengan filter, agar bisa memeriksa operasional kapan pun.

1. **Given** halaman riwayat, **When** filter (cabang, rentang tanggal, status transaksi, status bayar) diterapkan, **Then** daftar sesuai filter dan hanya milik bisnis owner.

### US-502 — Laporan pendapatan berbasis pembayaran
FR: FR-O11; aturan 7.5 · M5

Sebagai owner, saya ingin melihat pendapatan per periode berdasarkan uang yang benar-benar diterima, agar laporan mencerminkan kas.

1. **Given** DP Rp30.000 diterima 28 Juli dan pelunasan Rp44.500 pada 2 Agustus, **When** laporan Juli dan Agustus dibuka, **Then** Juli mencatat Rp30.000 dan Agustus Rp44.500.
2. **Given** transaksi dibatalkan setelah ada pembayaran, **When** laporan dibuka, **Then** pembayaran transaksi itu tidak dihitung.
3. **Given** filter "bulan ini" per cabang, **When** dijalankan, **Then** total = penjumlahan `payments.jumlah` pada bulan itu untuk transaksi tidak-batal cabang tersebut.

### US-503 — Daftar tagihan berjalan
FR: FR-O12 · M5

Sebagai owner, saya ingin melihat semua sisa tagihan cucian yang masih di laundry, agar uang yang belum masuk terpantau.

1. **Given** transaksi aktif `BELUM_BAYAR`/`DP`, **When** daftar dibuka, **Then** tiap baris menampilkan sisa tagihan (total akhir − terbayar) dan ada total keseluruhan; transaksi `SUDAH_DIAMBIL`/`DIBATALKAN` tidak muncul.

### US-504 — Dashboard ringkasan harian owner
FR: FR-O13 · M5

Sebagai owner, saya ingin ringkasan harian lintas cabang dalam kartu angka besar, agar kondisi bisnis terbaca sekali pandang.

1. **Given** dashboard dibuka, **When** data tampil, **Then** ada kartu: transaksi & total kg masuk hari ini, pendapatan hari ini, jumlah "Siap Diambil" menumpuk > X hari, total tagihan berjalan — mengikuti aturan desain `prd.md` 12.2.

### US-505 — Grafik pendapatan
FR: FR-O15 · M5

1. **Given** rentang tanggal dipilih, **When** grafik dimuat, **Then** pendapatan per hari/bulan tampil konsisten dengan angka US-502.

### US-506 — Ekspor CSV
FR: FR-O16 · M5

1. **Given** riwayat terfilter, **When** "Ekspor CSV" ditekan, **Then** file CSV berisi baris sesuai filter dengan kolom utama transaksi (kode, cabang, pelanggan, status, status bayar, total, terbayar, tanggal).

---

## Epic M6 — Mode Demo & PWA

### US-601 — Coba Demo satu klik
FR: FR-M01, FR-M02 · M6

Sebagai prospek, saya ingin mencoba aplikasi lengkap tanpa mendaftar, agar yakin sebelum berlangganan.

1. **Given** halaman depan, **When** "Coba Demo" ditekan, **Then** tercipta bisnis demo terisolasi berisi data contoh (1–2 cabang, layanan master + cabang termasuk express/kg/item, 1 promo aktif, program stempel aktif, saklar DP aktif, pelanggan, transaksi aneka status termasuk satu ber-DP), dan prospek langsung masuk sebagai owner demo.
2. **Given** dua prospek berbeda, **When** masing-masing membuat demo, **Then** keduanya mendapat tenant terpisah yang tidak saling terlihat.

### US-602 — Banner demo & ganti peran
FR: FR-M03 · M6

Sebagai prospek, saya ingin berpindah antara pandangan owner dan admin tanpa logout, agar bisa merasakan kedua sisi aplikasi.

1. **Given** sesi demo, **When** halaman mana pun dibuka, **Then** banner "MODE DEMO" tampil dengan tombol "Lihat sebagai Admin"/"Kembali sebagai Owner".
2. **Given** bisnis non-demo, **When** endpoint ganti peran dipanggil, **Then** ditolak (fitur khusus demo).

### US-603 — Notifikasi ditekan di demo
FR: FR-M04 · M6

1. **Given** transaksi demo menjadi `SIAP_DIAMBIL`, **When** alur notifikasi berjalan, **Then** tidak ada email/WA nyata terkirim dan log berstatus `ditekan_demo` tercatat sehingga alurnya tetap terlihat.

### US-604 — Pembersihan & pembatasan demo
FR: FR-M05, FR-M06 · M6

1. **Given** bisnis demo berumur > 7 hari, **When** penjadwal pembersihan berjalan, **Then** tenant demo beserta seluruh datanya terhapus.
2. **Given** satu IP telah membuat 3 demo hari ini, **When** mencoba membuat demo ke-4, **Then** ditolak dengan pesan batas harian.

### US-605 — Aplikasi dapat di-install (PWA)
FR: FR-W01 · M6

1. **Given** admin/owner membuka aplikasi di HP, **When** memasangnya ke home screen, **Then** aplikasi terpasang dengan ikon, nama, warna tema, dan splash screen sesuai manifest.

### US-606 — Service worker & pembaruan aset
FR: FR-W02 · M6

1. **Given** aplikasi ter-install, **When** dibuka ulang, **Then** aset statis termuat dari cache (lebih cepat) sementara data selalu diambil dari server.
2. **Given** deploy baru dilakukan, **When** pengguna membuka aplikasi, **Then** aset terbaru otomatis terpakai (nama file ber-hash).
3. **Given** perangkat offline, **When** aplikasi dibuka, **Then** tampil halaman "Anda sedang offline" — tidak ada fungsi offline lain.

# User Stories & Kriteria Penerimaan — CekLaundry

> **Kedudukan dokumen:** turunan dari `prd.md`. Setiap story merujuk ID kebutuhan (`FR-…`) dan milestone (M1–M6). Kriteria penerimaan memakai format Given-When-Then dan menjadi dasar pengujian otomatis. Jika ada pertentangan, `prd.md` menang. Semua story wajib diselesaikan.

Peran dalam story: **Pelanggan** (tanpa login), **Admin** (pegawai cabang), **Owner** (pemilik bisnis), **Developer** (pengelola aplikasi), **Prospek** (calon pemilik laundry, mode demo).

---

## Epic M1 — Fondasi & Tenant

### US-101 — Login & wajib ganti password
FR: FR-A01, FR-O01, FR-D01, FR-D02 · M1

Sebagai pengguna ber-akun (developer/owner/admin), saya ingin masuk dengan email + password dan dipaksa mengganti password awal, agar akun saya aman sejak awal.

1. **Given** akun aktif dengan kredensial benar, **When** login, **Then** pengguna diarahkan ke panel sesuai perannya.
2. **Given** akun berpenanda `must_change_password`, **When** login berhasil, **Then** pengguna dipaksa ke halaman ganti password dan hanya dapat membuka ganti password/logout pada panel; halaman publik tetap tersedia.
3. **Given** akun dinonaktifkan (`is_active = false`) atau bisnisnya dinonaktifkan developer, **When** login, **Then** akses ditolak dengan pesan yang jelas.
4. **Given** pengguna lupa password, **When** meminta reset via email, **Then** tautan reset terkirim dan dapat dipakai sekali.
5. **Given** bisnis `BACA_SAJA` dan owner harus mengganti password awal, **When** mengirim perubahan password, **Then** perubahan keamanan akun berhasil sementara operasi bisnis tetap ditolak 423; logout dan lupa/reset password juga tetap tersedia.
6. **Given** link reset60menit atau password sementara operator, **When** reset/ganti berhasil, **Then** token habis dan sesi dicabut sesuai PRD 9.3; akun nonaktif tetap tidak dapat login; demo tidak mengirim email reset.
7. **Given** login/reset burst atau signup publik, **When** endpoint diakses, **Then** rate limit SEC-02 berlaku dan signup tidak tersedia; request reset selalu respons generik.

8. **Given** auth reset job tertunda lalu token diganti/password berubah atau akun demo, **When** worker mengeksekusi, **Then** token lama/demo tidak dikirim; payload terenkripsi dan tidak masuk notification_logs transaksi.
9. **Given** OUTBOUND_RESTORE_HOLD aktif setelah restore, **When** request reset atau job reset lama diproses, **Then** respons request tetap generik tetapi tidak ada token/job baru atau SMTP call; job/token lama dibuang saat rekonsiliasi, pengguna meminta reset baru setelah re-enable.

### US-102 — Developer mendaftarkan bisnis baru
FR: FR-D02 · M1

Sebagai developer, saya ingin mendaftarkan bisnis laundry baru beserta akun owner-nya, agar pelanggan baru aplikasi bisa langsung mulai.

1. **Given** form bisnis baru terisi (nama bisnis, nama/email/password awal owner, masa aktif), **When** disimpan, **Then** tercipta satu `business`, satu user `owner` berpenanda wajib ganti password, satu baris `business_settings` dan `loyalty_settings` default.
2. **Given** email owner sudah dipakai user lain, **When** disimpan, **Then** ditolak dengan pesan validasi.
3. **Given** bisnis baru tercipta, **When** pengaturan loyalti dibaca, **Then** satu row berisi `is_active=false`, `stempel_dibutuhkan=10`, `master_service_id=null`, dan `berat_maks_gratis=null`.
4. **Given** provisioning gagal sesudah user dibuat, **When** DB rollback, **Then** tidak ada bisnis/user/settings parsial; satu bisnis hanya mempunyai satu owner dan active_until wajib untuk bisnis nyata.

### US-103 — Developer mengelola masa aktif & daftar bisnis
FR: FR-D03, FR-D04 · M1

Sebagai developer, saya ingin melihat semua bisnis beserta statusnya dan mengubah masa aktifnya, agar aktivasi langganan manual mudah dikelola.

1. **Given** panel developer dibuka, **When** melihat daftar bisnis, **Then** tampil nama, status (aktif/tenggang/baca-saja/nonaktif/demo), masa aktif, jumlah cabang, dan jumlah transaksi 30 hari terakhir — tanpa akses ke data transaksi/pelanggan mana pun.
2. **Given** sebuah bisnis, **When** developer mengubah `active_until`, menonaktifkan/mengaktifkan, atau mereset password owner, **Then** perubahan berlaku seketika dan tercatat di `audit_logs`.
3. **Given** developer membuka angka transaksi 30 hari/cabang, **When** meminta detail, pencarian, pelanggan, atau pembayaran bisnis, **Then** akses ditolak; API statistik hanya mengembalikan angka agregat tanpa ID/baris operasional.
4. **Given** transaksi tepat batas30×24jam dan cabang nonaktif, **When** statistik dibaca, **Then** perhitungan mengikuti PRD 7.10 termasuk batal/nonaktif, tanpa rows/IDs individual atau drill-down.

### US-104 — Siklus masa aktif: peringatan → tenggang → baca-saja
FR: FR-D05 · M1

Sebagai owner, saya ingin diberi peringatan bertahap saat masa aktif menipis, agar operasional tidak terputus mendadak.

1. **Given** masa aktif habis ≤ 7 hari lagi, **When** owner membuka panel, **Then** banner peringatan tampil.
2. **Given** hari WIB kini berada pada tanggal active_until+1 sampai active_until+7 (tenggang), **When** owner/admin bekerja, **Then** seluruh fungsi berjalan normal dengan banner mencolok.
3. **Given** tenggang telah lewat (baca-saja), **When** owner/admin login, **Then** login berhasil dan data terbaca, tetapi setiap tulis bisnis ditolak server 423 dan tombol bisnis nonaktif; logout, ganti password awal, serta lupa/reset password tetap bekerja.
4. **Given** bisnis dalam mode baca-saja, **When** penjadwal notifikasi berjalan, **Then** tidak ada notifikasi otomatis terkirim untuk bisnis itu.
5. **Given** bisnis dalam mode baca-saja atau nonaktif, **When** pelanggan membuka `/t/{kode_resi}` transaksinya, **Then** halaman status tetap tampil normal.
6. **Given** bisnis `BACA_SAJA` atau `NONAKTIF`, **When** pelanggan membuka status, **Then** form email tidak tersedia; POST perubahan email langsung ditolak server tanpa mengubah transaksi/customer.
7. **Given** NONAKTIF dengan sesi lama atau demo expired, **When** membuka panel, **Then** ditolak meski cookie login masih ada; hanya GET resi tenant nyata tetap tersedia.
8. **Given** BACA_SAJA, **When** link wa.me dari data scoped dibuka, **Then** boleh tanpa log; email manual dan seluruh business-write ditolak, keamanan akun tetap tersedia.

### US-105 — Owner mengelola cabang
FR: FR-O02 · M1

Sebagai owner, saya ingin menambah dan mengubah cabang, agar data cabang tampil benar di resi dan halaman status.

1. **Given** form cabang terisi (nama, alamat, telepon), **When** disimpan, **Then** cabang tercipta dan datanya dipakai di resi & halaman status.
2. **Given** cabang masih memiliki transaksi aktif (belum diambil & belum dibatalkan), **When** owner mencoba menonaktifkannya, **Then** ditolak dengan pesan yang menjelaskan alasannya.
3. **Given** cabang nonaktif, **When** owner membuka laporan, **Then** riwayat transaksi cabang itu tetap tampil.
4. **Given** cabang nonaktif tanpa transaksi aktif, **When** owner mengubah identitas/mengaktifkan kembali, **Then** master baru tampil pada resi/status dan create baru dapat dilakukan; harga historis tetap.
5. **Given** create transaksi bersamaan penonaktifan cabang, **When** diproses, **Then** salah satu ditolak sesuai urutan lock; tidak ada cabang nonaktif berisi transaksi aktif.

### US-106 — Owner mengelola akun admin
FR: FR-O03 · M1

Sebagai owner, saya ingin mendaftarkan admin dan menugaskannya ke satu cabang, agar tiap cabang punya operator.

1. **Given** form admin terisi (nama, email, password awal, cabang), **When** disimpan, **Then** admin tercipta dengan penanda wajib ganti password dan terikat ke tepat satu cabang.
2. **Given** satu cabang, **When** owner menambahkan admin kedua ke cabang yang sama, **Then** diperbolehkan (satu cabang boleh banyak admin).
3. **Given** admin lupa password, **When** owner menekan "Reset password", **Then** password sementara input owner disimpan dan must_change_password=true dan aksi tercatat di `audit_logs`.
4. **Given** owner mengubah nama/email/penugasan atau menonaktifkan admin, **When** sesi lama membuat request, **Then** policy membaca data terbaru; cabang lama tidak terbuka, akun nonaktif ditolak; perubahan email mencabut sesi/token reset alamat lama. ID tenant/role di payload tidak dapat mengubah otoritas.
5. **Given** reset password oleh owner, **When** password sementara disimpan, **Then** must_change_password=true, sesi/token lama dicabut, audit tidak menyimpan rahasia.

### US-107 — Owner mengelola Layanan Master
FR: FR-O04 · M1

Sebagai owner, saya ingin mengelola daftar layanan di satu tempat, agar tidak mengatur ulang dari nol untuk tiap cabang.

1. **Given** form layanan master terisi (nama, satuan kg/item, harga, durasi jam, berat minimum opsional), **When** disimpan, **Then** layanan master tercipta; nama unik per bisnis.
2. **Given** layanan express diinginkan, **When** owner membuat layanan berdurasi lebih pendek dan berharga lebih tinggi, **Then** tidak ada fitur khusus lain yang diperlukan.
3. **Given** owner mengubah/menonaktifkan master, **When** disimpan, **Then** transaksi lama tetap snapshot dan perubahan master tidak otomatis mengubah cabang; harga/durasi/quantity domain PRD 7.8 divalidasi.
4. **Given** nama berbeda kapital/spasi berulang atau master dipakai hadiah aktif, **When** duplikat/ubah satuan/nonaktif dicoba, **Then** aturan unique normalisasi dan penjagaan hadiah PRD 7.8 ditegakkan.

### US-108 — Sinkronisasi layanan master ke cabang
FR: FR-O05 · M1

Sebagai owner, saya ingin menyalin/memperbarui layanan cabang dari master dengan aturan timpa yang jelas, agar mengubah harga banyak cabang cukup sekali kerja.

1. **Given** cabang tanpa layanan, **When** "Salin/Perbarui dari Master" dijalankan, **Then** seluruh layanan master aktif tersalin ke cabang.
2. **Given** cabang memiliki layanan bernama sama dengan master namun harga berbeda, **When** sinkronisasi dijalankan, **Then** layanan cabang itu ditimpa mengikuti master (harga, durasi, berat minimum, satuan).
3. **Given** cabang memiliki layanan khusus yang tidak ada di master, **When** sinkronisasi dijalankan, **Then** layanan khusus itu tidak disentuh.
4. **Given** sinkronisasi akan memperbarui 5 layanan dan menambah 2 layanan, **When** owner menekan tombolnya, **Then** pratinjau "5 diperbarui, 2 ditambahkan" tampil lebih dulu dan eksekusi butuh konfirmasi.
5. **Given** halaman master, **When** owner memakai "Sebarkan ke Cabang" dengan mencentang beberapa cabang, **Then** aturan 1–4 berlaku untuk setiap cabang tercentang.
6. **Given** transaksi lama dengan harga snapshot, **When** sinkronisasi mengubah harga cabang, **Then** transaksi lama tidak berubah.
7. **Given** layanan lokal bernama sama nonaktif dan master aktif, **When** sync dikonfirmasi, **Then** lokal diaktifkan kembali dan semua atribut mengikuti master; master nonaktif tidak disalin.
8. **Given** data berubah setelah preview atau master di-rename, **When** save, **Then** fingerprint stale409; nama master baru menjadi layanan baru sesuai preview, nama lokal lama tetap. Seluruh cabang pilihan atomik dan audit tersimpan.
9. **Given** owner mengedit layanan cabang langsung, **When** harga diubah, **Then** hanya cabang itu berubah dan snapshot transaksi lama tetap.

### US-109 — Isolasi tenant & cabang
FR: Bagian 3.2 `prd.md`; ISO-01–ISO-06 · M1

Sebagai pemilik data, saya ingin data bisnis dan cabang terisolasi mutlak, agar tidak ada kebocoran antarbisnis.

1. **Given** user bisnis A, **When** mengakses URL/ID resource milik bisnis B (transaksi, pelanggan, laporan, pengaturan), **Then** respons 404 — bukan 403 — tanpa membocorkan keberadaan data.
2. **Given** admin cabang 1, **When** mengakses transaksi/dashboard cabang 2 bisnis yang sama, **Then** respons 404/daftar kosong.
3. **Given** developer, **When** mencoba membuka data transaksi/pelanggan bisnis mana pun, **Then** akses ditolak oleh policy.
4. **Given** record transaksi/layanan/pembayaran/status/notifikasi/loyalti baru, **When** service menyimpannya, **Then** `business_id` harus cocok dengan parent, cabang, dan customer; ID lintas bisnis ditolak meski dikirim langsung ke API.
5. **Given** admin mencari customer lintas cabang dalam bisnis, **When** hasil/detail dibuka, **Then** identitas dan saldo total boleh, tetapi transaksi/ledger/payment/log cabang lain tidak dapat dilihat; report/export owner403.
6. **Given** job dua tenant berurutan atau tanpa konteks, **When** query dibuat, **Then** context dibersihkan dan konteks kosong fail closed; credentials/DTO/audit tidak bocor.

---

## Epic M2 — Operasional Inti

### US-201 — Halaman depan & pencarian kode resi
FR: FR-C01, FR-C05 · M2

Sebagai pelanggan, saya ingin langsung mengecek status dari halaman depan, agar tidak perlu bertanya ke laundry.

1. **Given** halaman depan dibuka, **When** halaman tampil, **Then** kolom input kode resi + tombol "Cek Status" adalah elemen utama, dan tombol "Coba Demo" tersedia.
2. **Given** kode resi valid dimasukkan, **When** "Cek Status" ditekan, **Then** pelanggan diarahkan ke `/t/{kode_resi}`.
3. **Given** kode tidak terdaftar, **When** dicari, **Then** tampil pesan "Kode resi tidak ditemukan, periksa kembali resi Anda" tanpa informasi lain.
4. **Given** implementasi baru M2, **When** halaman depan tampil, **Then** tombol demo berlabel belum tersedia; sejak M6 tombol memanggil provision, tanpa memindahkan milestone demo.

### US-202 — Halaman status publik
FR: FR-C02, FR-C03, FR-C06 · M2

Sebagai pelanggan, saya ingin melihat detail dan progres cucian saya di satu halaman, agar tahu kapan harus mengambil dan berapa sisa tagihan saya.

1. **Given** transaksi berkode `K7F3XA`, **When** `/t/K7F3XA` dibuka (termasuk via scan QR), **Then** tampil: timeline status + waktu tiap tahap, rincian item, potongan (jika ada), total akhir, terbayar & sisa tagihan, status bayar, estimasi selesai, catatan kondisi, dan info cabang dengan telepon yang bisa diklik.
2. **Given** halaman status publik, **When** data pelanggan ditampilkan, **Then** nama dan no. HP tersamar sebagian (mis. "Rad*** — 0812***678").
3. **Given** pengecekan kode berulang cepat dari satu IP, **When** melewati ambang rate limit, **Then** permintaan berikutnya ditolak sementara (SEC-02).
4. **Given** direct URL, cetak publik, form cek, endpoint email dan verify, **When** kode salah/berulang diminta, **Then** berbagi limiter dan pesan non-enumeratif; tidak ada endpoint alternatif yang melewati batas.
5. **Given** payload publik diperiksa dan browser offline/back setelah logout, **When** dirender, **Then** tidak ada email/ID/actor/log/full phone tersembunyi atau data tenant lama; header/no-cache sesuai PRD 9.1.

### US-203 — Membuat transaksi baru
FR: FR-A02, FR-A03, FR-A04; aturan 7.1 & 7.7 · M2

Sebagai admin, saya ingin mencatat cucian masuk beserta layanannya dalam waktu kurang dari satu menit, agar antrean pelanggan cepat terlayani.

1. **Given** pelanggan lama, **When** admin mengetik no. HP/nama, **Then** data pelanggan ditemukan tanpa input ulang; **Given** pelanggan baru, **Then** cukup nama + no. HP (email opsional).
2. **Given** item "Cuci+Setrika 3,5 kg @Rp7.000" dan "Bed Cover 2 item @Rp25.000", **When** transaksi disimpan, **Then** subtotal Rp24.500 dan Rp50.000, total Rp74.500, dan nama+satuan+harga layanan serta subtotal final ter-snapshot pada item.
3. **Given** layanan berberat minimum 3 kg, **When** berat diisi 2 kg, **Then** subtotal dihitung memakai 3 kg.
4. **Given** kolom catatan kondisi diisi "noda di kerah", **When** disimpan, **Then** catatan tampil di resi dan halaman status.
5. **Given** transaksi tersimpan, **When** kode resi dibuat, **Then** kode 6 karakter tanpa O/0/I/1/L dan unik global.
6. **Given** pelanggan membawa cucian express dan reguler yang ingin diambil terpisah, **When** admin mencatat, **Then** dicatat sebagai dua resi terpisah (panduan 7.7 tampil sebagai petunjuk di form).
7. **Given** customer memiliki email saat transaksi dibuat, **When** transaksi disimpan, **Then** email itu disalin ke `transactions.notification_email`; perubahan email customer di kemudian hari tidak mengubah transaksi lama.
8. **Given** qty kg0/negatif/lebih satu desimal, itempecahan, uang overflow atau item>100, **When** save, **Then** ditolak tanpa data parsial; perkiraan jumlah baju opsional tidak memengaruhi harga.
9. **Given** save create berhasil tetapi respons hilang, **When** UUID+hash sama diulang, **Then** satu transaksi/payment/history saja; payload beda409. Cabang/customer/waktu/creator tidak dapat diubah lewat edit.
10. **Given** harga/promo/settings berubah setelah preview, **When** save, **Then**409 dengan quote baru, tidak memakai nominal client atau mengubah harga diam-diam.

### US-204 — Estimasi selesai otomatis
FR: aturan 7.3 · M2

Sebagai admin, saya ingin estimasi selesai terisi otomatis namun bisa dikoreksi, agar akurat tanpa menghitung manual.

1. **Given** transaksi berisi layanan berdurasi 48 jam dan 6 jam, **When** disimpan pukul 08.00, **Then** estimasi = waktu masuk + 48 jam.
2. **Given** antrean sedang penuh dan transaksi masih `DITERIMA`, **When** admin mengubah estimasi manual, **Then** nilai manual berlaku di resi/halaman status; sejak `DIPROSES` perubahan estimasi ditolak.
3. **Given** edit finansial masih sah, **When** layanan berubah, **Then** estimasi default dihitung ulang dari durasi snapshot terbaru kecuali estimasi manual dikirim; nilai sebelum waktu_masuk ditolak. Edit catatan/perkiraan baju tidak mereset estimasi/harga.

### US-205 — Memperbarui status cucian
FR: FR-A05; Bagian 6 · M2

Sebagai admin, saya ingin memperbarui status satu arah dengan jejak lengkap, agar progres bisa dipercaya.

1. **Given** transaksi `DITERIMA`, **When** admin mengubah ke `DIPROSES` lalu `SIAP_DIAMBIL`, **Then** tiap perubahan tercatat di riwayat (status, waktu, pengguna) dan `waktu_siap_diambil` terisi.
2. **Given** transaksi `SIAP_DIAMBIL`, **When** admin mencoba mengembalikannya ke `DIPROSES` atau request lain melompat dari `DITERIMA` langsung ke `SIAP_DIAMBIL`, **Then** transisi mundur ditolak; transisi maju hanya sah ke tahap tepat berikutnya.
3. **Given** transaksi `DITERIMA`, `DIPROSES`, atau `SIAP_DIAMBIL`, **When** dibatalkan tanpa alasan, **Then** ditolak; dengan alasan, status menjadi `DIBATALKAN`.
4. **Given** lima status dan seluruh pasangan transition, **When** diminta, **Then** hanya tiga maju dan tiga pembatalan sah; same-state retry no-op, terminal tidak dapat dibuka/batal, history DITERIMA awal dan timestamps konsisten.
5. **Given** dua tab memakai versi status lama, **When** save, **Then**409 tanpa overwrite atau notifikasi tambahan.

### US-206 — Mencatat pembayaran & uang muka (DP)
FR: FR-A06, FR-O07; aturan 7.4 · M2

Sebagai admin, saya ingin mencatat pembayaran penuh maupun DP sebagai catatan permanen, agar keuangan akurat dan tidak bisa diutak-atik.

1. **Given** transaksi belum dibayar, total Rp74.500 dan business_settings.dp_enabled=true saat payment, **When** admin mencatat DP Rp30.000 (tunai), **Then** status bayar menjadi `DP`, sisa Rp44.500 tampil di resi/halaman status.
2. **Given** transaksi ber-DP tersebut, **When** pelunasan Rp44.500 dicatat saat pengambilan, **Then** status bayar `LUNAS` dan penyerahan diizinkan.
3. **Given** sisa tagihan Rp44.500, **When** admin mencoba mencatat Rp50.000, **Then** ditolak (melebihi sisa).
4. **Given** transaksi dibuat ketika DP aktif, belum ada pembayaran, lalu owner mematikan DP, **When** admin mencoba partial payment pertama, **Then** ditolak tanpa payment/history/ledger baru; aturan memakai saklar saat payment, bukan saat create.
5. **Given** catatan pembayaran tersimpan, **When** siapa pun mencoba mengubah/menghapusnya, **Then** tidak ada jalur untuk itu (tidak ada endpoint).
6. **Given** total akhir Rp0, **When** transaksi dibuat atau dihitung ulang sebelum terkunci, **Then** `status_bayar=LUNAS`, tidak ada baris pembayaran Rp0, dan penyerahan saat siap diambil diizinkan.
7. **Given** sisa Rp50.000, **When** dua request masing-masing Rp50.000 datang bersamaan, **Then** hanya satu berhasil; total pembayaran tetap ≤ total akhir dan status bayar konsisten.
8. **Given** transaksi dibuat ketika DP nonaktif dan belum dibayar, lalu owner mengaktifkan DP, **When** partial payment pertama dicatat, **Then** diperbolehkan sesuai setting terkini dan status menjadi DP.
9. **Given** respons payment hilang, **When** request_key/hash sama diulang, **Then** hasil payment lama tanpa insert; payload berbeda409. Payment terminal, jumlah0/negatif, atau tanggal backdate ditolak.
10. **Given** payment beradu edit/batal, **When** dieksekusi, **Then** serial berdasarkan root lock dan sisa; cancel dahulu menolak payment, payment dahulu boleh tercatat tetapi dikeluarkan dari pendapatan setelah batal.
11. **Given** total Rp100.000 belum dibayar dan DP nonaktif, **When** admin membayar seluruh Rp100.000 pada transaksi yang sah menerima payment, **Then** diperbolehkan dan status menjadi LUNAS.
12. **Given** total Rp100.000 sudah DP Rp30.000 lalu owner mematikan DP, **When** admin menambah Rp20.000, **Then** diperbolehkan, terbayar Rp50.000 dan status tetap DP; overpay tetap ditolak.
13. **Given** total Rp100.000 sudah DP Rp30.000 dan DP kini nonaktif, **When** admin membayar sisa Rp70.000, **Then** diperbolehkan dan status menjadi LUNAS, efek loyalti tetap hanya sekali jika eligible.
14. **Given** transaksi belum dibayar dan DP aktif, **When** toggle-off berlomba dengan partial payment pertama pada dua koneksi MySQL memakai business root lock, **Then** off commit dahulu menolak partial; partial commit dahulu membuat DP berjalan yang tetap dapat dicicil setelah off, tanpa state parsial/nondeterministik.
15. **Given** initial payment saat create atau request payment berhasil yang responsnya hilang, **When** saklar berubah, **Then** initial payment baru memeriksa setting sesudah root lock dan gagal atomik bila partial tak sah; replay request_key/hash payment yang sudah commit tetap mengembalikan hasil lama tanpa insert ulang meski DP kini off.

### US-207 — Larangan penyerahan sebelum lunas
FR: FR-A07 · M2

Sebagai owner, saya ingin cucian hanya bisa diserahkan setelah lunas, agar bisnis tidak menanggung hutang pelanggan.

1. **Given** transaksi `SIAP_DIAMBIL` berstatus bayar `BELUM_BAYAR` atau `DP`, **When** admin mencoba mengubah ke `SUDAH_DIAMBIL`, **Then** ditolak dengan pesan sisa tagihan.
2. **Given** pelunasan dicatat, **When** status diubah ke `SUDAH_DIAMBIL`, **Then** berhasil dan `waktu_diambil` terisi.

### US-208 — Kunci transaksi & pemulihan sebelum penyerahan lewat pembatalan
FR: FR-A15, FR-A16; aturan 7.6 · M2

Sebagai owner, saya ingin transaksi terkunci sejak diproses dan kesalahan dipulihkan lewat pembatalan berjejak, agar tidak ada manipulasi data.

1. **Given** transaksi `DITERIMA` belum LUNAS, tanpa pembayaran dan tanpa `loyalty_histories`, **When** admin mengedit item/berat/jumlah/promo, **Then** diperbolehkan dan total serta status bayar dihitung ulang.
2. **Given** transaksi `DITERIMA` sudah dibayar Rp80.000 dari total Rp100.000, **When** admin/owner mencoba mengubah item sehingga total Rp60.000, **Then** perubahan ditolak dan tidak terjadi overpay.
3. **Given** transaksi `DITERIMA` sudah menukar stempel, **When** admin/owner mencoba mengubah item, berat, jumlah, promo, atau penukaran, **Then** perubahan ditolak.
4. **Given** transaksi `DITERIMA` sudah dibayar atau mempunyai riwayat stempel, **When** catatan kondisi/estimasi selesai diubah, **Then** perubahan berhasil tanpa mengubah angka keuangan.
5. **Given** transaksi `DIPROSES` atau setelahnya, **When** admin **atau owner** mencoba mengedit field operasional transaksi, **Then** ditolak — tidak ada fitur buka-kunci; FR-C04 sebelum `SIAP_DIAMBIL` tetap boleh memperbarui email transaksi. Edit identitas master/merge dan penyesuaian WA US-213/214 bukan edit field operasional transaksi.
6. **Given** salah timbang ketahuan saat `DIPROSES`, **When** admin membatalkan (dengan alasan) lalu membuat transaksi baru yang benar, **Then** transaksi lama keluar dari pendapatan, pembayarannya dikecualikan dari laporan, pelaku/alasan tercatat di `audit_logs`, dan transaksi baru berjalan normal; kompensasi stempel saat program M4 tersedia diuji di US-402.
7. **Given** transaksi `DITERIMA` bertotal Rp0 sudah `LUNAS` dan memperoleh +1 stempel tanpa payment, **When** admin/owner mengubah item atau promo, **Then** edit harga ditolak karena sudah ada `loyalty_histories` meski tidak ada payment/penukaran.
8. **Given** Rp0 LUNAS saat loyalty mati, **When** edit harga, **Then** tetap ditolak; edit sah yang menghasilkan0 langsung LUNAS dan mengunci save finansial berikutnya.
9. **Given** transaksi DITERIMA, **When** edit catatan/perkiraan baju/estimasi dengan versi benar, **Then** harga/snapshot tetap; sejak DIPROSES seluruh edit ini ditolak.
10. **Given** pembatalan dua kali/paralel atau setelah SUDAH_DIAMBIL, **When** dijalankan, **Then** satu pembatalan/kompensasi untuk aktif, retry no-op, terminal diserahkan ditolak. Pembayaran historis tidak dihapus/dinolkan.

### US-209 — Resi, QR, dan cetak thermal
FR: FR-A08, FR-R01, FR-R02, FR-R03, FR-R04 · M2

Sebagai admin, saya ingin mencetak resi 58 mm ber-QR, agar pelanggan bisa cek status dengan sekali scan.

1. **Given** transaksi tersimpan, **When** resi dicetak, **Then** memuat: identitas cabang, kode resi, tanggal masuk, pelanggan, rincian item + harga satuan + subtotal, potongan (jika ada), total akhir, terbayar & sisa, status bayar, estimasi selesai, catatan kondisi, QR berisi `/t/{kode}`, dan catatan kaki link cek status.
2. **Given** printer thermal 58 mm, **When** dialog print browser dipakai, **Then** tata letak rapi pada lebar ±48 mm dan QR terpindai.
3. **Given** link halaman status dibagikan, **When** dibuka, **Then** berfungsi sebagai resi bentuk web.
4. **Given** resi dicetak dari publik dibanding panel berotorisasi, **When** dilihat, **Then** publik tetap masked dan internal memuat identitas penuh; CSS tidak menyembunyikan payload sensitif. Resi batal menampilkan label batal tanpa menghapus payment.

### US-210 — Kirim resi via WhatsApp manual
FR: FR-A09 · M2

Sebagai admin, saya ingin mengirim ringkasan resi lewat `wa.me` tanpa biaya API, agar pelanggan langsung pegang link statusnya.

1. **Given** transaksi tersimpan dan no. HP pelanggan `62…`, **When** "Kirim resi via WhatsApp" ditekan, **Then** terbuka `wa.me/{no_hp}` berisi teks ringkasan (kode, total, sisa tagihan, estimasi, link `/t/{kode}`).
2. **Given** M3 writable/read-only, atau M6 demo, **When** kirim resi, **Then** masing-masing dibuka_manual dengan key request, link tanpa log, atau ditekan_demo+preview tanpa membuka WhatsApp nyata; jumlah kuota API tidak berubah.

### US-211 — Dashboard admin
FR: FR-A10, FR-A11 · M2

Sebagai admin, saya ingin melihat pekerjaan hari ini, cucian menumpuk, dan yang terlambat dalam satu layar, agar tahu prioritas.

1. **Given** dashboard dibuka, **When** data tampil, **Then** ada: transaksi hari ini, transaksi aktif per status, daftar "Siap Diambil — belum diambil" terurut umur menunggu (hari), dan daftar "Terlambat" (melewati estimasi, belum siap).
2. **Given** admin cabang 1, **When** dashboard tampil, **Then** hanya data cabang 1.
3. **Given** transaksi batal/diambil dan berat min lebih besar dari aktual, **When** dashboard, **Then** daftar hari ini berlabel semua status, late hanya DITERIMA/DIPROSES, siap urut waktu lalu ID, umur floor24jam; tidak memasukkan terminal sebagai terlambat.

### US-212 — Pencarian transaksi
FR: FR-A12 · M2

Sebagai admin, saya ingin mencari transaksi via kode/nama/no. HP, agar pelanggan yang kehilangan resi tetap terlayani.

1. **Given** kata kunci kode resi, nama, atau no. HP, **When** pencarian dijalankan, **Then** transaksi cocok di cabang admin tampil; kombinasi dengan US-210 memungkinkan kirim ulang link status.
2. **Given** kata kunci customer bisnis lain/cabang lain atau injeksi query, **When** search, **Then** hasil terscope dan bound, tidak ada ID/log/detail tersembunyi dari luar cabang.

### US-213 — Kelola data pelanggan
FR: FR-A13 · M2

Sebagai admin, saya ingin menambah/mengedit pelanggan dengan no. HP sebagai identitas unik, agar riwayat pelanggan tidak terputus.

1. **Given** no. HP yang sudah terdaftar di bisnis, **When** admin menambah pelanggan baru dengan no. HP sama, **Then** ditolak (unik per bisnis).
2. **Given** dua pelanggan berbeda bernama sama dengan no. HP berbeda, **When** keduanya disimpan, **Then** diperbolehkan sebagai dua pelanggan.
3. **Given** pelanggan lama ganti nomor, **When** admin mengedit no. HP-nya, **Then** seluruh riwayat transaksi & stempel tetap melekat (terikat ID internal).
4. **Given** 08…/+62…/62… ekuivalen dan nama/email diubah, **When** save, **Then** nomor canonical62… unique per bisnis, update master berlaku untuk identitas bersama, notification_email transaksi lama tidak berubah.
5. **Given** sejak M3 WA ready direservasi ke A, delivery_started_at null dan attempt_count=0, **When** admin mengubah nomor customer menjadi B, **Then** tujuan pending menjadi B dengan notification_key/reminder_number/slot yang sama, claim lama dibatalkan; hanya B dapat menerima kiriman dari log belum attempted itu pada provider sehat.
6. **Given** WA mempunyai delivery_started_at atau attempt_count>0 termasuk retry dengan marker null, **When** nomor berubah A→B, **Then** log nonterminal menjadi perlu_pemeriksaan dengan recipient_berubah, tujuan/payload/attempt/bucket tetap, token dicabut; tidak retry ke A atau dialihkan ke B, accepted terlambat tidak menimpa review. Call sudah diotorisasi/in-flight tidak dapat ditarik kembali.
7. **Given** WA pending belum attempted telah diselaraskan setelah edit A→B, **When** A didaftarkan customer baru dan job lama/recovery dijalankan, **Then** tidak ada kiriman transaksi customer lama ke A; lookup melalui customer_id transaksi, key tetap, worker stale gagal CAS.
8. **Given** customer bersama mempunyai WA pending di dua cabang bisnis yang sama, **When** admin mengedit nomor secara sah, **Then** pemeliharaan internal log kedua cabang atomik tanpa membuka ID/log/transaksi cabang lain; tenant lain tidak berubah dan notification_email/tujuan email tetap snapshot.
9. **Given** edit nomor bersaing preflight atau kemudian nomor B berubah kembali ke A, **When** dieksekusi, **Then** edit sebelum marker membatalkan claim lama; marker dahulu menutup log ke review tanpa retry meski in-flight mungkin selesai; A→B→A tidak menghidupkan terminal. Kegagalan penyesuaian log rollback edit dan pelepasan nomor.

### US-214 — Gabung pelanggan duplikat
FR: FR-A14 · M2

Sebagai admin, saya ingin menggabungkan pelanggan ganda, agar stempel dan riwayat tidak terpecah.

1. **Given** pelanggan sumber & tujuan satu bisnis dipilih oleh owner atau memenuhi batas cabang admin FR-A14, **When** penggabungan dikonfirmasi, **Then** seluruh transaksi dan ledger stempel sumber pindah ke tujuan, nama/no. HP/email tujuan dipertahankan, saldo tujuan dihitung ulang sebagai `SUM(jumlah)` gabungan, sumber dihapus, dan aksi tercatat di `audit_logs`.
2. **Given** dialog penggabungan, **When** belum dikonfirmasi eksplisit, **Then** tidak ada perubahan data.
3. **Given** dua customer dengan transaksi aktif/historis, HP/email berbeda dan ledger saldo negatif, **When** owner merge, **Then** target identity, snapshot finansial/email dan ledger delta tetap, cache=SUM gabungan; log WA mengikuti AC6–9 sejak M3, source phone bebas hanya sesudah commit seluruh perubahan.
4. **Given** salah satu memiliki transaksi cabang lain termasuk batal, **When** admin merge, **Then**403 generik tanpa detail; owner satu bisnis boleh. Source=target/cross-tenant ditolak.
5. **Given** merge bersamaan create/payment/redemption/cancel atau merge balik, **When** keduanya selesai, **Then** protokol lock menghasilkan urutan sah tanpa orphan/double ledger; pending verifikasi email source gugur, pending verifikasi target tidak diubah.
6. **Given** source bernomor A mempunyai WA pending belum attempted dan target bernomor B, **When** merge sah dilakukan, **Then** FK transaksi/ledger pindah ke target dan tujuan WA pending menjadi B dengan notification_key/slot sama; claim lama dicabut, source dihapus terakhir dalam satu commit.
7. **Given** merge AC6 telah commit dan nomor source A dilepas, **When** customer baru memakai A lalu queue lama/recovery berjalan, **Then** tidak ada WA transaksi source lama terkirim ke customer baru; log belum attempted memakai B, tidak mencari customer berdasarkan tujuan A.
8. **Given** WA source sudah provider attempt tetapi belum finalize, **When** source A di-merge ke target B sebelum callback accepted/definite rejection, **Then** log perlu_pemeriksaan dengan recipient_berubah, token dicabut, tujuan/payload/bucket asal tetap, tidak ada retry/retarget; callback lama tidak menimpa review, in-flight dapat tetap mencapai provider tanpa jaminan penarikan.
9. **Given** penyesuaian recipient gagal atau merge admin melibatkan histori cabang lain, **When** merge dicoba, **Then** masing-masing rollback seluruh FK/ledger/log/nomor atau tolak403 tanpa detail; email transaksi dan tujuan log email tetap snapshot, pending verifikasi source saja gugur pada merge yang berhasil.

### US-215 — Owner mengerjakan operasional lintas cabang
FR: FR-O06 · M2

Sebagai owner yang turun tangan sendiri, saya ingin memakai seluruh fungsi admin di semua cabang saya, agar bisa menggantikan pegawai kapan pun.

1. **Given** owner membuka panel operasional, **When** memilih salah satu cabangnya, **Then** seluruh kemampuan US-203–US-214 tersedia untuk cabang itu.
2. **Given** owner, **When** membuat transaksi, **Then** tercatat sebagai pembuat transaksi tersebut.
3. **Given** M3/M4 tersedia, **When** owner memakai notifikasi/promo/penukaran cabang pilihannya, **Then** FR-A17/A18 juga tersedia dengan policy dan invariants yang sama; tidak ada hak buka-kunci khusus owner.

---

## Epic M3 — Notifikasi

### US-301 — Email otomatis "Siap Diambil"
FR: FR-N01 · M3

Sebagai pelanggan, saya ingin diberi tahu lewat email saat cucian selesai, agar tidak datang sia-sia.

1. **Given** `notification_email` transaksi terisi, **When** status menjadi `SIAP_DIAMBIL`, **Then** pada provider sehat yang mengonfirmasi accepted, satu email untuk identitas logis (`transaction_id`, `siap_diambil`, `email`, 0) dikirim berisi info laundry, kode, total, sisa bila ada, dan link status.
2. **Given** job diulang atau render diulang, **When** peristiwa yang sama diproses, **Then** kanal dengan log berhasil tidak dikirim dua kali; hasil email tidak menentukan hasil WA.
3. **Given** pelanggan tanpa email, **When** status menjadi `SIAP_DIAMBIL`, **Then** tidak ada email dan tidak ada error.
4. **Given** ready job tertunda lalu transaksi diambil/dibatalkan/tenant read-only sebelum preflight, **When** job dijalankan, **Then** dilewati_kondisi tanpa send; state berubah sesudah otorisasi boleh menyisakan pesan in-flight, link status terkini.
5. **Given** backup dibuat sebelum email yang kemudian berhasil dikirim lalu DB direstore, **When** worker hidup, **Then** OUTBOUND_RESTORE_HOLD memblokir SMTP sampai rekonsiliasi; DB tidak mengklaim mengetahui send yang hilang, tidak otomatis replay window RPO setelah re-enable (US-308 AC10–13).

### US-302 — Pengingat otomatis cucian belum diambil
FR: FR-N02 · M3

Sebagai owner, saya ingin pelanggan diingatkan berkala, agar cucian selesai tidak menumpuk.

1. **Given** pengaturan default N=2, M=2, K=3 dan transaksi `SIAP_DIAMBIL` sejak 2 hari lalu dengan kanal eligible dan provider sehat, **When** penjadwal harian berjalan, **Then** pengingat pertama terkirim dan `reminder_count` = 1.
2. **Given** pengingat sudah 3 kali, **When** penjadwal berjalan lagi, **Then** tidak ada pengingat tambahan.
3. **Given** transaksi diambil (`SUDAH_DIAMBIL`), **When** penjadwal berjalan, **Then** transaksi itu tidak menerima pengingat.
4. **Given** owner mengubah N/M/K, **When** penjadwal berjalan, **Then** aturan baru dipakai.
5. **Given** satu nomor pengingat jatuh tempo, **When** scheduler/job dipicu ulang, **Then** email dan WA masing-masing memakai identitas logis sendiri dengan nomor tersebut; nomor berikutnya baru dijadwalkan sesuai interval dan batas K.
6. **Given** scheduler terlambat beberapa interval atau overlap, **When** berjalan, **Then** hanya satu nomor baru/tx/putaran, next dihitung dari last reservation; tidak burst catch-up.
7. **Given** kanal tak tersedia atau WA penuh, **When** due, **Then** tanpa kanal cursor tetap, WA eligible penuh log skipped dan nomor terpakai; gagal/unknown tetap menghitung satu nomor, manual tidak.
8. **Given** pengingat dimatikan/K diturunkan/diaktifkan, **When** scheduling/preflight, **Then** pending otomatis dihentikan sesuai state, cursor tidak reset, aturan N/M/K kini berlaku.

9. **Given** switch off→on atau lifecycle expired→diperpanjang sebelum worker sempat berjalan, **When** pending diperiksa, **Then** pending lama telah ditutup, bukan dikirim ulang ketika aktif kembali.
10. **Given** transaksi sudah SIAP_DIAMBIL sebelum/saat restore hold, **When** scheduler berjalan selama hold atau setelah outbound_resume_at, **Then** tidak membuat reservasi/cursor/slot/kiriman catch-up untuk transaksi itu; reminder otomatis hanya untuk waktu_siap_diambil>cutoff sesudah re-enable, bukan sekadar log baru dibuat setelah cutoff.

### US-303 — Pelanggan mendaftarkan email transaksi
FR: FR-C04 · M3

Sebagai pelanggan, saya ingin memasukkan email untuk resi ini tanpa mengubah identitas master pelanggan.

1. **Given** transaksi DITERIMA/DIPROSES pada tenant writable, **When** email valid diminta, **Then** pending+versi+expiry24jam dan log/job verifikasi disimpan atomik; email aktif transaksi dan master customer tetap.
2. **Given** email invalid atau request melebihi3/jam/resi,10/hari/IP,5/jam/recipient atau shared30/menit/IP, **When** POST dicoba termasuk secara paralel, **Then** validasi/429 tanpa mengganti email aktif atau mengirim job tambahan.
3. **Given** signed link terbaru, **When** GET, **Then** hanya konfirmasi tampil; **When** POST sah sebelum expiry, **Then** hanya notification_email transaksi berubah, pending habis, version naik; customers.email dan transaksi lain selalu tetap meski pemegang resi menguasai email baru.
4. **Given** link terpakai/terganti/expired atau transaksi sudah siap/batal/diambil, **When** konfirmasi, **Then** ditolak tanpa mutasi; job verifikasi stale tidak terkirim.
5. **Given** BACA_SAJA/NONAKTIF, **When** halaman status dibuka, **Then** tetap tampil tanpa form aktif; request/konfirmasi POST423, GET link tidak mengubah apa pun.
6. **Given** pending lalu transition ready atau merge source, **When** commit, **Then** pending source gugur, notifikasi memakai snapshot email aktif sebelumnya; target merge dan master tidak diubah. Pending transaksi target tetap boleh diverifikasi hanya ke transaksi target.
7. **Given** edit customer.email oleh admin kemudian, **When** notifikasi transaksi lama diproses, **Then** snapshot transaksi tidak ikut berubah; email master boleh dipakai saat membuat transaksi baru.
8. **Given** konfirmasi bersamaan ready/lifecycle/merge, **When** keduanya berjalan, **Then** protokol lock menghasilkan salah satu urutan sah, tidak lolos memakai versi/status lama.

### US-304 — Ingatkan pelanggan secara manual
FR: FR-A17 · M3

Sebagai admin, saya ingin tombol pengingat manual, agar bisa menindak cucian menumpuk kapan saja.

1. **Given** transaksi `SIAP_DIAMBIL`, **When** "Ingatkan pelanggan" ditekan, **Then** email pengingat terkirim ulang (bila ber-email) dan/atau `wa.me` terbuka berisi teks pengingat; aksi tercatat di log notifikasi.
2. **Given** `wa.me` manual dibuka tetapi pengguna membatalkan pengiriman, **When** log diperiksa, **Then** status hanya `dibuka_manual`, bukan `berhasil`; penghitungan batas WA otomatis tidak berubah.
3. **Given** double-click/response hilang, **When** request UUID sama diulang, **Then** satu log/key/send; key baru dibatasi1/10menit/tx+20/hari/user, hasil unknown sebelumnya meminta konfirmasi sadar risiko.
4. **Given** selain SIAP_DIAMBIL, read-only atau demo, **When** manual reminder, **Then** state salah/email read-only ditolak, wa.me read-only tanpa log, demo semua kanal preview tanpa outbound.

### US-305 — Konfigurasi teknis per bisnis (developer)
FR: FR-D06 · M3

Sebagai developer, saya ingin memegang seluruh konfigurasi teknis WA & email per bisnis, agar owner tidak pernah menyentuh hal teknis.

1. **Given** panel developer per bisnis, **When** konfigurasi diisi (penyedia WA, token, nomor pengirim; nama & alamat pengirim email; SMTP opsional), **Then** credential tersimpan terenkripsi pada TEXT; form developer hanya menunjukkan configured indicator dan input pengganti, tidak pernah mengembalikan rahasia tersimpan.
2. **Given** WA belum dikonfigurasi/dinonaktifkan, **When** aplikasi berjalan, **Then** seluruh fungsi tetap utuh via email + `wa.me` manual.
3. **Given** dua tenant berbeda SMTP/WA dan worker panjang, **When** job bergantian, **Then** transport/context tenant tidak saling terbawa; provider/token/sender wajib lengkap untuk aktivasi, fallback email default ketika belum configured.

4. **Given** adapter WABA/Wablas dipilih tanpa phone ID/template/version/bahasa atau server/secret yang diwajibkan, **When** WA diaktifkan, **Then** ditolak sampai wa_config lengkap; template WABA memakai lima parameter sesuai architecture 6.3 dan tidak fallback free-text.

### US-306 — Pengaturan perilaku notifikasi (owner)
FR: FR-O14 · M3

Sebagai owner, saya ingin mengatur perilaku notifikasi tanpa istilah teknis, agar bisa mengendalikan pengalaman pelanggan dan biaya.

1. **Given** halaman pengaturan owner, **When** dibuka, **Then** tersedia: saklar pengingat + N/M/K, saklar WA per peristiwa (Siap Diambil / pengingat), batas WA bulanan, dan penghitung WA berhasil untuk bulan kuota serta slot pending/tidak pasti terpisah — tanpa token/istilah teknis.
2. **Given** N/M/K nol atau melebihi255 meski switch off, **When** save, **Then** ditolak; threshold menumpuk tetap positif. WA limit null/0 sah, lowering di bawah occupied bulan kini ditolak.

### US-307 — WhatsApp otomatis & kendali biaya
FR: FR-N03, FR-N04 · M3

Sebagai owner, saya ingin WA otomatis yang biayanya terkendali, agar pelanggan terlayani tanpa tagihan membengkak.

1. **Given** WA aktif (kredensial terpasang) dan saklar peristiwa "Siap Diambil" menyala, **When** transaksi menjadi `SIAP_DIAMBIL`, **Then** WA terkirim via adapter penyedia bisnis itu dan log `berhasil` tercatat.
2. **Given** saklar peristiwa pengingat mati, **When** pengingat jatuh tempo, **Then** hanya email terkirim.
3. **Given** batas bulanan 100 dan sudah 100 WA `berhasil` bulan ini, **When** peristiwa ke-101 terjadi, **Then** WA tidak dikirim, email tetap terkirim, log `dilewati_batas` tercatat.
4. **Given** bulan berganti tanpa reservasi di bulan baru, **When** penghitung dibaca, **Then** bucket bulan baru nol; bucket lama tetap tersimpan dan pesan in-flight lintas tengah malam tidak memindahkan slot.
5. **Given** batas 100 dan sudah 99 WA berhasil, **When** dua kanal WA baru mereservasi slot pada bisnis yang sama secara bersamaan, **Then** hanya satu mendapat slot; yang lain `dilewati_batas`, emailnya tetap berjalan, dan hitungan slot `tertunda`/`diproses`/`berhasil`/`perlu_pemeriksaan` tidak melampaui 100.
6. **Given** pending WA melewati bulan sebelum attempt pertama, **When** preflight, **Then** reserve bucket kini atau skipped bila penuh; accepted lintas tengah malam tetap bucket otorisasi pertama.
7. **Given** retry definite-rejection pada bulan berikutnya, **When** job due, **Then** dilewati_kondisi tanpa call; unknown memegang slot asal, retry/key sama tidak memesan dua slot.
8. **Given** provider HTTP200 dengan body error atau SMTP disconnect setelah DATA, **When** adapter menilai, **Then** tidak menganggap sukses tanpa accepted; unknown tanpa fallback/auto retry.
9. **Given** WA pending diubah recipient oleh edit/merge, **When** dispatcher/retry/recovery berjalan termasuk pergantian bulan, **Then** US-213/214 ditegakkan sebelum retry/kuota; retarget belum attempted memakai key/slot sama, review pernah-attempt mempertahankan bucket/slot dan tidak melepasnya akibat callback lama.
10. **Given** backup dibuat sebelum WA yang kemudian accepted, **When** DB direstore tanpa log kiriman tersebut, **Then** OUTBOUND_RESTORE_HOLD memblokir WA; tidak mengarang log/bukti send yang hilang atau menjanjikan deduplikasi window RPO; re-enable mengikuti US-308 AC10–13.

### US-308 — Log notifikasi & keandalan
FR: FR-N05, FR-N06 · M3

Sebagai admin, saya ingin melihat riwayat notifikasi per transaksi dan yakin kegagalan kirim tidak mengganggu operasional.

1. **Given** detail transaksi dibuka, **When** melihat bagian notifikasi, **Then** tampil semua log (kanal, tipe, tujuan, status, waktu).
2. **Given** penyedia WA/SMTP pasti menolak tanpa menerima pesan, **When** status diubah ke `SIAP_DIAMBIL`, **Then** perubahan status tetap sukses; maksimal 3 panggilan total dengan backoff60/300detik lalu tercatat `gagal`.
3. **Given** email berhasil dan WA gagal untuk peristiwa sama, **When** WA dicoba ulang, **Then** email tidak terkirim ulang; setiap kanal memakai baris/logical key terpisah.
4. **Given** hasil pengiriman ke penyedia tidak diketahui setelah timeout, **When** job diulang, **Then** log menjadi terminal otomatis `perlu_pemeriksaan` pada semua provider, tanpa pengiriman ulang; UI memberi peringatan sebelum kontak manual baru.
5. **Given** admin membuka `wa.me` untuk resi atau pengingat, **When** log dicatat, **Then** kanal `whatsapp_manual`, tipe `resi`/`pengingat`, status `dibuka_manual`, dan tidak masuk hitungan WA API berhasil.
6. **Given** proses mati setelah log `tertunda` dibuat, **When** transaksi DB belum commit, **Then** log dan job queue sama-sama batal; bila sudah commit, keduanya ada dan job dapat diproses.
7. **Given** worker mati sesudah mengklaim log `diproses` tetapi sebelum delivery marker, **When** lease kedaluwarsa, **Then** claim dapat diambil worker baru tepat sekali. Bila pemanggilan penyedia sudah dimulai dan hasilnya tak pasti, log masuk `perlu_pemeriksaan` tanpa retry otomatis meskipun provider mendukung idempotency key.
8. **Given** job tertunda hilang atau process mati setelah marker sebelum call/after accepted before finalize, **When** recovery, **Then** pending yang masih eligible diantre ulang ≤6menit pada scheduler sehat, marker ada menjadi unknown tanpa resend, accepted terlambat hanya boleh finalisasi token sama yang masih sah; token dicabut recipient/restore tidak boleh finalize.
9. **Given** stale worker mencoba CAS setelah reclaim atau queue:retry terminal, **When** diproses, **Then** tidak dapat memanggil provider; failed job/error hanya metadata aman bagi developer.
10. **Given** backup01.00, email/WA berhasil10.01 dan kerusakan23.00, **When** restore backup01.00, **Then** log send10.01 memang tidak tersedia dan sistem tidak mengklaim mengetahui outcome yang hilang; OUTBOUND_RESTORE_HOLD=true sebelum instance pulih, tanpa automatic send sampai rekonsiliasi operator.
11. **Given** restore hold aktif dengan pending/recovered/retry/manual/verifikasi/auth job, **When** worker/scheduler hidup sebelum rekonsiliasi, **Then** tidak ada provider SMTP/WA call, attempt/slot/cursor baru; operasi status/payment aman tetap commit tanpa backlog outbound; log nonterminal yang ada masuk review dengan token dicabut dan slot existing tetap.
12. **Given** operator telah memeriksa recovery point/periode hilang, mereview log dan membuang outbound/auth job serta token reset/pending verifikasi lama, **When** cutoff outbound_resume_at dipasang pada cutover terkoordinasi dan hold dilepas, **Then** hanya log/request baru >cutoff boleh send dan ready/reminder juga harus waktu_siap_diambil>cutoff; tidak ada replay/catch-up historis, waktu sama cutoff ditolak konservatif, event baru eligible sesudahnya berjalan normal.
13. **Given** aplikasi restart setelah re-enable atau job/callback lama terlewat cleanup, **When** recovery/transport/finalize dijalankan, **Then** cutoff deployment tetap berlaku, log lama masuk review, token tercabut tidak finalize, auth token lama tidak sah; manual/verifikasi/auth request baru sesudah cutoff boleh sesuai policy. Re-enable tidak menjamin deduplikasi terhadap external effect yang hilang dalam window RPO.

---

## Epic M4 — Loyalti & Promo

### US-401 — Pengaturan program stempel
FR: FR-L01, FR-O09 · M4

Sebagai owner, saya ingin mengatur program stempel, agar pelanggan terdorong kembali.

1. **Given** form pengaturan (aktif, N, layanan gratis satuan-kg dari master, berat maks), **When** disimpan, **Then** program berlaku untuk seluruh cabang bisnis.
2. **Given** program nonaktif, **When** transaksi berjalan, **Then** tidak ada perolehan/penawaran penukaran dan halaman status tidak menampilkan stempel.
3. **Given** program nonaktif dengan layanan hadiah/berat maks kosong, **When** disimpan, **Then** valid; **When** diaktifkan tanpa N≥1, master layanan kg milik bisnis, dan berat maks >0, **Then** ditolak.
4. **Given** hadiah master di-rename/tidak tersedia/beda satuan di cabang, **When** redemption ditawarkan, **Then** hanya nama-normalized+kg aktif yang cocok, bukan menebak mapping; owner harus sinkron/ubah layanan.
5. **Given** program dimatikan lalu diaktifkan, **When** transaksi lama sudah LUNAS, **Then** tidak ada earning retroaktif, ledger dan compensation lama tetap berlaku.

### US-402 — Perolehan & pencabutan stempel
FR: FR-L02 · M4

Sebagai pelanggan, saya ingin stempel bertambah tiap transaksi lunas, agar hadiah gratis makin dekat.

1. **Given** program aktif, **When** transaksi mencapai `LUNAS` (dan tidak dibatalkan), **Then** stempel pelanggan +1 dan `loyalty_histories` mencatat perolehan — berlaku lintas cabang.
2. **Given** transaksi pemberi stempel dibatalkan, **When** pembatalan diproses, **Then** stempel tersebut dicabut dan tercatat.
3. **Given** transaksi hasil penukaran, **When** mencapai `LUNAS`, **Then** **tidak** menambah stempel.
4. **Given** transaksi penukaran N=10 dibatalkan setelah N berubah, **When** dibatalkan, **Then** entry `pengembalian_penukaran` bernilai +10 ditambah tanpa menghapus `penukaran` −10.
5. **Given** transaksi pemberi stempel dibatalkan, **When** dibatalkan, **Then** entry `pencabutan_perolehan` bernilai −1 ditambah; pembatalan tidak menggandakan kompensasi.
6. **Given** Rp0 LUNAS dengan program aktif dan tanpa redemption, **When** create atau edit sah menghasilkan0, **Then** tepat+1; program off tidak memberi+1 dan enable sesudahnya tidak retroaktif.
7. **Given** earning telah dibelanjakan lalu transaksi asal dibatalkan ketika program mati/N berubah, **When** kompensasi, **Then** −1 asal tetap dicatat meski saldo negatif; transaksi pemakai tidak dibatalkan, earning baru mengurangi utang.

### US-403 — Penukaran stempel
FR: FR-L03, FR-A18 · M4

Sebagai pelanggan setia, saya ingin menukar stempel dengan cucian gratis berbatas berat, agar loyalitas saya dihargai.

1. **Given** N=10, hadiah "Cuci+Setrika maks 3 kg" @Rp7.000, pelanggan berstempel 10 membawa 5 kg, **When** penukaran diterapkan, **Then** potongan Rp21.000, sisa 2 kg dibayar (Rp14.000), stempel berkurang 10, item bertanda hadiah.
2. **Given** pelanggan yang sama membawa 2 kg pada layanan tanpa minimum berat, **When** ditukar, **Then** potongan Rp14.000 dan sisa kuota 1 kg hangus.
3. **Given** stempel pelanggan < N, **When** transaksi dibuat, **Then** penawaran penukaran tidak muncul.
4. **Given** satu transaksi, **When** admin mencoba menukar dua kali, **Then** ditolak (maksimal satu penukaran per transaksi).
5. **Given** saldo 10 dan dua transaksi menukar masing-masing 10 bersamaan, **When** keduanya diproses, **Then** lock customer membuat hanya satu berhasil; penukaran tidak membuat saldo negatif.
6. **Given** actual2kg,min3kg,@7000,rewardmax3, **When** ditebus, **Then** subtotal21000,stamp14000,sisa7000 sebelum promo; satu item dipilih bila beberapa cocok, potongan0 ditolak.
7. **Given** config N/harga berubah setelah quote atau edit finansial masih sah ingin menambah redemption, **When** save, **Then** quote stale409; save sah menulis −N sekali dan mengunci harga; tidak ada refund/redemption-edit tanpa pembatalan.

### US-404 — Riwayat stempel
FR: FR-L04 · M4

Sebagai owner, saya ingin melihat riwayat stempel tiap pelanggan, agar program bisa diaudit.

1. **Given** halaman pelanggan, **When** riwayat dibuka, **Then** tampil `perolehan`, `penukaran`, `pengembalian_penukaran`, `pencabutan_perolehan` beserta delta bertanda, transaksi, waktu; saldo = `SUM(jumlah)` dan sama dengan cache `stamp_count`.
2. **Given** admin cabang1 melihat saldo global customer, **When** membuka ledger, **Then** hanya baris cabang1 disertakan, tidak ada link/ID operasi cabang2; owner melihat semua dan SUM ledger gabungan sesudah merge dapat diaudit.

### US-405 — Stempel di halaman status
FR: FR-C07 · M4

Sebagai pelanggan, saya ingin melihat jumlah stempel saya saat cek status, agar tahu jarak ke hadiah.

1. **Given** program stempel aktif, **When** `/t/{kode}` dibuka, **Then** tampil "Stempel Anda: 7/10"; **Given** program nonaktif, **Then** bagian ini tidak tampil.
2. **Given** pembatalan transaksi pemberi stempel setelah stempel itu terpakai menyebabkan saldo −1 dan target 10, **When** halaman status dibuka, **Then** tampil `Stempel Anda: −1/10` beserta penjelasan bahwa satu stempel perlu diperoleh kembali; angka tidak dibulatkan menjadi nol.

### US-406 — Kelola promo
FR: FR-P01, FR-O08 · M4

Sebagai owner, saya ingin membuat promo bernama dengan masa berlaku dan cakupan cabang, agar diskon terkontrol penuh olehku.

1. **Given** form promo (nama, tipe persen/nominal, nilai, minimal total opsional, periode, semua/sebagian cabang), **When** disimpan, **Then** promo tersedia bagi admin pada cabang & periode yang sesuai.
2. **Given** owner mengedit/menonaktifkan promo atau mengisi persen>100/nominal0/tanggal terbalik/cabang asing, **When** save, **Then** field invalid ditolak, konfigurasi sah hanya memengaruhi pemilihan/save finansial berikutnya; snapshot lama tetap.

### US-407 — Menerapkan promo pada transaksi
FR: FR-P02, FR-P03, FR-A18; aturan 7.2 · M4

Sebagai admin, saya ingin memilih satu promo dari daftar yang sah, agar potongan konsisten dengan kebijakan owner.

1. **Given** promo aktif pada cabang & periode berlaku dan minimal total terpenuhi, **When** dipilih di transaksi, **Then** potongan dihitung sesuai 7.2 dan nama/tipe/nilai promo di-snapshot ke transaksi.
2. **Given** promo kedaluwarsa, di luar cabang, atau minimal tak terpenuhi, **When** admin memilih promo, **Then** promo itu tidak tersedia/ditolak validasi.
3. **Given** transaksi memakai penukaran stempel dan promo persen 10%, **When** total dihitung, **Then** persen diambil dari subtotal **setelah** potongan stempel, dibulatkan ke rupiah terdekat, dan total akhir tidak pernah negatif.
4. **Given** promo diubah/dinonaktifkan owner, **When** transaksi lama dilihat, **Then** angka transaksi lama tidak berubah.
5. **Given** subtotal Rp100.000, potongan stempel Rp20.000, dan `minimal_total` Rp90.000, **When** promo dipilih, **Then** ditolak karena `promo_eligible_base` Rp80.000. Pada minimum Rp80.000, promo 10% memberi Rp8.000 atau promo nominal Rp100.000 dibatasi Rp80.000; `total_akhir` tidak negatif.
6. **Given** satu promo dan satu hadiah bersamaan atau promo nominal>base, **When** save, **Then** base/subtotal/potongan/total PRD 7.2 persis, total0 tanpa payment0; promo kedua/potongan bebas ditolak.

---

## Epic M5 — Laporan Owner

### US-501 — Riwayat transaksi & filter
FR: FR-O10 · M5

Sebagai owner, saya ingin menelusuri semua transaksi dengan filter, agar bisa memeriksa operasional kapan pun.

1. **Given** halaman riwayat, **When** filter (cabang, rentang tanggal, status transaksi, status bayar) diterapkan, **Then** daftar sesuai filter dan hanya milik bisnis owner.
2. **Given** batas tanggal WIB dan status batal, **When** filter, **Then** riwayat memakai waktu_masuk rentang half-open PRD 7.10; batal tetap dapat tampil dan cabang nonaktif tidak menghilangkan histori.

### US-502 — Laporan pendapatan berbasis pembayaran
FR: FR-O11; aturan 7.5 · M5

Sebagai owner, saya ingin melihat pendapatan per periode berdasarkan uang yang benar-benar diterima, agar laporan mencerminkan kas.

1. **Given** DP Rp30.000 diterima 28 Juli dan pelunasan Rp44.500 pada 2 Agustus, **When** laporan Juli dan Agustus dibuka, **Then** Juli mencatat Rp30.000 dan Agustus Rp44.500.
2. **Given** transaksi dibatalkan setelah ada pembayaran, **When** laporan dibuka, **Then** pembayaran transaksi itu tidak dihitung.
3. **Given** filter "bulan ini" per cabang, **When** dijalankan, **Then** total = penjumlahan `payments.jumlah` pada bulan itu untuk transaksi tidak-batal cabang tersebut.
4. **Given** pembatalan September atas DP Juli/pelunasan Agustus, **When** laporan lama dibuka ulang, **Then** kedua periode berubah mengecualikan pembayaran tersebut; tidak mengarang refund September.

### US-503 — Daftar tagihan berjalan
FR: FR-O12 · M5

Sebagai owner, saya ingin melihat semua sisa tagihan cucian yang masih di laundry, agar uang yang belum masuk terpantau.

1. **Given** transaksi aktif `BELUM_BAYAR`/`DP`, **When** daftar dibuka, **Then** tiap baris menampilkan sisa tagihan (total akhir − terbayar) dan ada total keseluruhan; transaksi `SUDAH_DIAMBIL`/`DIBATALKAN` tidak muncul.

### US-504 — Dashboard ringkasan harian owner
FR: FR-O13 · M5

Sebagai owner, saya ingin ringkasan harian lintas cabang dalam kartu angka besar, agar kondisi bisnis terbaca sekali pandang.

1. **Given** `reminder_first_days=2`, **When** dashboard dibuka, **Then** kartu menumpuk menghitung transaksi `SIAP_DIAMBIL` dengan `waktu_siap_diambil` berusia ≥ 2 hari tepat pada waktu query; kartu lain menampilkan transaksi & total kg masuk hari ini, pendapatan hari ini, serta tagihan berjalan.
2. **Given** min weight lebih besar dari aktual, multiple payments, batal dan reminder switch off, **When** kartu dirender, **Then** kg aktual/jumlah nonbatal tidak terduplikasi dan threshold tetap N×24jam.

### US-505 — Grafik pendapatan
FR: FR-O15 · M5

1. **Given** rentang tanggal dipilih, **When** grafik dimuat, **Then** pendapatan per hari/bulan tampil konsisten dengan angka US-502.
2. **Given** bucket tanpa payment dan rentang hari/bulan, **When** grafik, **Then** bucket nol tampil, tanggal WIB dan total sama dengan laporan tanpa cache agregat usang.

### US-506 — Ekspor CSV
FR: FR-O16 · M5

1. **Given** riwayat terfilter, **When** "Ekspor CSV" ditekan, **Then** file CSV berisi baris sesuai filter dengan kolom utama transaksi (kode, cabang, pelanggan, status, status bayar, total, terbayar, tanggal).
2. **Given** customer bernama teks formula dan report berubah saat export, **When** CSV, **Then** UTF8BOM/RFC4180/teks formula safe, kolom persis PRD 7.10, satu snapshot baca scoped owner, tanpa data antar-tenant.

---

## Epic M6 — Mode Demo & PWA

### US-601 — Coba Demo satu klik
FR: FR-M01, FR-M02 · M6

Sebagai prospek, saya ingin mencoba aplikasi lengkap tanpa mendaftar, agar yakin sebelum berlangganan.

1. **Given** halaman depan, **When** "Coba Demo" ditekan, **Then** tercipta bisnis demo terisolasi berisi data contoh (tepat 2 cabang, layanan master + cabang termasuk express/kg/item, 1 promo aktif, program stempel aktif, saklar DP aktif, pelanggan, transaksi aneka status termasuk satu ber-DP), dan prospek langsung masuk sebagai owner demo.
2. **Given** dua prospek berbeda, **When** masing-masing membuat demo, **Then** keduanya mendapat tenant terpisah yang tidak saling terlihat.
3. **Given** fixture selesai, **When** diperiksa, **Then** persis6customer/15transaksi/2cabang PRD 9.4 dengan ledger sumber saldo, minimum/harga/payment/history konsisten dan komunikasi sintetis tidak keluar.

### US-602 — Banner demo & ganti peran
FR: FR-M03 · M6

Sebagai prospek, saya ingin berpindah antara pandangan owner dan admin tanpa logout, agar bisa merasakan kedua sisi aplikasi.

1. **Given** sesi demo, **When** halaman mana pun dibuka, **Then** banner "MODE DEMO" tampil dengan tombol "Lihat sebagai Admin"/"Kembali sebagai Owner".
2. **Given** bisnis non-demo, **When** endpoint ganti peran dipanggil, **Then** ditolak (fitur khusus demo).
3. **Given** demo memiliki dua cabang, **When** prospek memilih "Lihat sebagai Admin", **Then** sistem memakai satu admin demo yang di-seed pada cabang pertama dan hanya menampilkan data cabang itu; kembali sebagai Owner memulihkan akses semua cabang demo.
4. **Given** sesi demo mengirim arbitrary user/branch atau menonaktifkan akun/cabang reserved, **When** request, **Then** ditolak; switch sah meregenerasi sesi dan selalu memakai admin Cabang Utama.

### US-603 — Notifikasi ditekan di demo
FR: FR-M04 · M6

1. **Given** transaksi demo menjadi `SIAP_DIAMBIL` dengan email serta WA otomatis eligible, **When** alur notifikasi berjalan, **Then** tidak ada email/WA nyata terkirim; dua log `ditekan_demo` terpisah berisi kanal dan tujuan masing-masing. Kanal yang tidak eligible tidak dibuatkan log.
2. **Given** reset password/verifikasi email/wa.me/telpon dicoba di demo, **When** dijalankan, **Then** tidak ada network delivery atau external link; log transaksi bertipe/kanal/key/tujuan snapshot yang disimulasikan, reset tanpa token/kiriman.

### US-604 — Pembersihan & pembatasan demo
FR: FR-M05, FR-M06 · M6

1. **Given** demo mencapai created_at+7×24jam, **When** request masuk, **Then** akses/sesi/status ditolak sebagai demo berakhir; **When** cron per menit berjalan, **Then** seluruh data demo terhapus ≤5menit pada sistem sehat, atau putaran pertama setelah scheduler pulih.
2. **Given** satu IP telah membuat 3 demo hari ini, **When** mencoba membuat demo ke-4, **Then** ditolak dengan pesan batas harian.
3. **Given** dua purge paralel dan ada session/job/ledger demo, **When** cleanup, **Then** child sebelum parent tanpa disable FK, semua live data domain/infra bersih dan tenant normal tidak tersentuh; retries idempoten.

### US-605 — Aplikasi dapat di-install (PWA)
FR: FR-W01 · M6

1. **Given** admin/owner membuka aplikasi di HP, **When** memasangnya ke home screen, **Then** aplikasi terpasang dengan ikon, nama, warna tema, dan splash screen sesuai manifest.

### US-606 — Service worker & pembaruan aset
FR: FR-W02 · M6

1. **Given** aplikasi ter-install, **When** dibuka ulang, **Then** aset statis termuat dari cache (lebih cepat) sementara data selalu diambil dari server.
2. **Given** deploy baru dilakukan, **When** pengguna membuka aplikasi, **Then** aset terbaru otomatis terpakai (nama file ber-hash).
3. **Given** perangkat offline, **When** aplikasi dibuka, **Then** tampil halaman "Anda sedang offline" — tidak ada fungsi offline lain.
4. **Given** form belum disimpan ketika deploy atau browser back sesudah switch tenant, **When** SW update/navigation, **Then** pembaruan tidak membuang input diam-diam dan data tenant lama tidak tampil dari cache; offline tidak menyimpan/mengirim ulang write.


## Matriks traceability FR → AC → database → arsitektur → NFR

Nomor AC menunjuk daftar bernomor pada story, bukan sekadar kemunculan ID. Satu FR dapat memerlukan AC lintas milestone untuk integrasi; milestone produk pada kolom tetap berasal dari PRD. Tes fondasi yang menyentuh fitur M3/M4/M6 dijalankan setelah fitur terkait tersedia, tanpa memindahkan label FR. Semua AC wajib diuji saat implementasi.

| FR | Milestone | Story dan AC perilaku | Database / derived | Service / arsitektur | NFR / uji |
|---|---|---|---|---|---|
| FR-C01 | M2 | US-201 AC 1,4 | transactions.kode_resi; UI tanpa tabel tambahan | Route publik arsitektur2/8 | SEC-02, KIN-01 |
| FR-C02 | M2 | US-202 AC 1,4 | transactions, transaction_items, payments, status_histories, customers, branches | Route publik arsitektur2/8 | SEC-02, SEC-06, AND-26 |
| FR-C03 | M2 | US-202 AC 1 | transactions, transaction_items, payments, status_histories, customers, branches | Route publik arsitektur2/8 | SEC-02, SEC-06, AND-26 |
| FR-C04 | M3 | US-303 AC 1,2,3,4,5,6,7,8; US-308 AC 11,12,13 | transactions.notification_email, transactions.pending_notification_email, transactions.email_verification_version, transactions.email_verification_expires_at, notification_logs, jobs | EmailVerificationService; restore arsitektur9 | SEC-09, SEC-08, AND-10, AND-27 |
| FR-C05 | M2 | US-201 AC 3 | transactions.kode_resi; UI tanpa tabel tambahan | Route publik arsitektur2/8 | SEC-02, KIN-01 |
| FR-C06 | M2 | US-202 AC 2,5 | transactions, transaction_items, payments, status_histories, customers, branches | Route publik arsitektur2/8 | SEC-02, SEC-06, AND-26 |
| FR-C07 | M4 | US-405 AC 1,2 | loyalty_settings, customers.stamp_count | Route publik arsitektur8 | UX-06, AND-16 |
| FR-A01 | M1 | US-101 AC 1,3,4,5,6,7,8,9 | users, businesses, sessions, password_reset_tokens | AccountService; arsitektur2/6.4/9 | SEC-01, SEC-02, SEC-08, AND-27 |
| FR-A02 | M2 | US-203 AC 1,2,7,8,9,10 | transactions, transaction_items, customers, services, payments, status_histories | TransactionService; PricingService; ReceiptCodeGenerator | AND-01, AND-08, AND-18, AND-22 |
| FR-A03 | M2 | US-203 AC 2,3,8 | transactions, transaction_items, customers, services, payments, status_histories | TransactionService; PricingService; ReceiptCodeGenerator | AND-01, AND-08, AND-18, AND-22 |
| FR-A04 | M2 | US-203 AC 4 | transactions, transaction_items, customers, services, payments, status_histories | TransactionService; PricingService; ReceiptCodeGenerator | AND-01, AND-08, AND-18, AND-22 |
| FR-A05 | M2 | US-205 AC 1,2,3,4,5 | transactions, status_histories | TransactionStateMachine | AND-14, AND-18 |
| FR-A06 | M2 | US-206 AC 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15 | payments, transactions.status_bayar, business_settings.dp_enabled | PaymentService; TransactionService; arsitektur4.1/4.2 | AND-02, AND-06, AND-13, AND-17, AND-18 |
| FR-A07 | M2 | US-207 AC 1,2 | transactions.status_bayar, payments | TransactionStateMachine | AND-02, AND-14 |
| FR-A08 | M2 | US-209 AC 1,2,4 | transactions, transaction_items, payments, branches, customers; QR derived | Route cetak arsitektur8; ReceiptCodeGenerator | SEC-06, AND-08, KOM-02 |
| FR-A09 | M2 | US-210 AC 1,2 | transactions, customers; notification_logs sejak M3 | Route manual arsitektur 6.4 | AND-25, SEC-08 |
| FR-A10 | M2 | US-211 AC 1,2,3 | transactions, transaction_items; umur derived | RevenueReportService; route app | AND-24, ISO-02 |
| FR-A11 | M2 | US-211 AC 1,3 | transactions, transaction_items; umur derived | RevenueReportService; route app | AND-24, ISO-02 |
| FR-A12 | M2 | US-212 AC 1,2 | transactions, customers | Route app scoped arsitektur3 | ISO-01, ISO-02, ISO-05 |
| FR-A13 | M2 | US-213 AC 1,2,3,4,5,6,7,8,9 | customers, transactions.customer_id, transactions.notification_email, notification_logs.tujuan, notification_logs.delivery_started_at, notification_logs.attempt_count, notification_logs.processing_token | CustomerService; NotificationDispatcher; arsitektur6.2.1 (WA sejak M3) | AND-08, AND-28, ISO-02, ISO-06 |
| FR-A14 | M2 | US-214 AC 1,2,3,4,5,6,7,8,9 | customers, transactions, loyalty_histories, audit_logs, notification_logs.tujuan, notification_logs.notification_key, notification_logs.processing_token | CustomerMergeService; NotificationDispatcher; arsitektur6.2.1 (WA sejak M3) | AND-15, AND-23, AND-28, ISO-02 |
| FR-A15 | M2 | US-208 AC 6,10; US-402 AC 4,5,7 | transactions, transaction_items, payments, loyalty_histories, audit_logs; loyalty_histories, customers.stamp_count, transactions | TransactionService; CancellationService; StampService; CancellationService | AND-09, AND-13, AND-14, AND-02, AND-07, AND-16, AND-23 |
| FR-A16 | M2 | US-208 AC 1,2,3,4,5,7,8,9 | transactions, transaction_items, payments, loyalty_histories, audit_logs | TransactionService; CancellationService | AND-09, AND-13, AND-14 |
| FR-A17 | M3 | US-304 AC 1,2,3,4; US-308 AC 11,12,13 | notification_logs, transactions, cache, cache_locks | NotificationDispatcher; manual arsitektur6.4; restore arsitektur9 | AND-04, AND-25, AND-27, SEC-08 |
| FR-A18 | M4 | US-403 AC 1,2,4,5,6,7; US-407 AC 3,5,6 | loyalty_settings, loyalty_histories, transactions.stamp_reward_max_kg_snapshot, transaction_items; transactions, promos, promo_branches, transaction_items | StampService; PricingService; PricingService | AND-07, AND-16, AND-22, AND-08 |
| FR-O01 | M1 | US-101 AC 1,2,3,4,5,6,8,9 | users, businesses, sessions, password_reset_tokens | AccountService; arsitektur2/6.4/9 | SEC-01, SEC-02, SEC-08, AND-27 |
| FR-O02 | M1 | US-105 AC 1,2,3,4,5 | branches, transactions | AccountService; arsitektur4 | AND-19, DAT-01 |
| FR-O03 | M1 | US-106 AC 1,2,3,4,5 | users, branches, audit_logs, sessions | AccountService | SEC-01, SEC-05, SEC-07, AND-19 |
| FR-O04 | M1 | US-107 AC 1,2,3,4 | master_services, loyalty_settings | MasterSyncService; PricingService | AND-08, AND-19, AND-22 |
| FR-O05 | M1 | US-108 AC 1,2,3,4,5,6,7,8,9 | master_services, services, branches, audit_logs | MasterSyncService | AND-01, AND-08, AND-19 |
| FR-O06 | M2 | US-215 AC 1,2,3 | users, branches, transactions | Policy arsitektur3; domain services | ISO-01, ISO-02, AND-09 |
| FR-O07 | M2 | US-206 AC 1,4,6,8,11,12,13,14,15 | payments, transactions.status_bayar, business_settings.dp_enabled | PaymentService; pengaturan owner arsitektur4.1/4.2 | AND-02, AND-06, AND-13, AND-17, AND-18 |
| FR-O08 | M4 | US-406 AC 1,2; US-407 AC 1,2,4 | promos, promo_branches; transactions, promos, promo_branches, transaction_items | PricingService; owner policy arsitektur3; PricingService | AND-08, AND-22, ISO-06 |
| FR-O09 | M4 | US-401 AC 1,2,3,4,5 | loyalty_settings, master_services | StampService | AND-16, AND-23 |
| FR-O10 | M5 | US-501 AC 1,2 | transactions, branches | RevenueReportService | AND-24, ISO-05 |
| FR-O11 | M5 | US-502 AC 1,2,3,4 | payments.waktu, payments.jumlah, transactions.status | RevenueReportService | AND-24 |
| FR-O12 | M5 | US-503 AC 1 | transactions, payments; sisa_tagihan derived | RevenueReportService | AND-02, AND-24 |
| FR-O13 | M5 | US-504 AC 1,2 | transactions, transaction_items.berat_kg, payments, business_settings.reminder_first_days | RevenueReportService | AND-24, KIN-04 |
| FR-O14 | M3 | US-306 AC 1,2 | business_settings, notification_logs.wa_quota_month | NotificationDispatcher; kuota arsitektur 6.3 | AND-11, AND-20 |
| FR-O15 | M5 | US-505 AC 1,2 | payments, transactions; agregat derived | RevenueReportService | AND-24 |
| FR-O16 | M5 | US-506 AC 1,2 | transactions, customers, branches, payments | RevenueReportService; CSV arsitektur8 | AND-24, ISO-05 |
| FR-D01 | M1 | US-101 AC 1,3,7 | users, businesses, sessions, password_reset_tokens | AccountService; arsitektur2/6.4 | SEC-01, SEC-02, SEC-08 |
| FR-D02 | M1 | US-102 AC 1,2,3,4; US-101 AC 2 | businesses, users, business_settings, loyalty_settings; users, businesses, sessions, password_reset_tokens | TenantProvisioner; AccountService; arsitektur2/6.4 | AND-01, ISO-06, SEC-01, SEC-02, SEC-08 |
| FR-D03 | M1 | US-103 AC 2; US-101 AC 6 | businesses, branches, transactions, users, audit_logs; users, businesses, sessions, password_reset_tokens | LifecycleService; agregat arsitektur3; AccountService; arsitektur2/6.4 | ISO-03, SEC-07, SEC-08, SEC-01, SEC-02 |
| FR-D04 | M1 | US-103 AC 1,3,4 | businesses, branches, transactions, users, audit_logs | LifecycleService; agregat arsitektur3 | ISO-03, SEC-07, SEC-08 |
| FR-D05 | M1 | US-104 AC 1,2,3,4,5,6,7,8 | businesses, users, sessions | LifecycleService | SEC-08, AND-21 |
| FR-D06 | M3 | US-305 AC 1,2,3,4 | businesses.wa_config, businesses.wa_token, businesses.smtp_config | Adapter arsitektur 6.3 | SEC-03, ISO-05, AND-27 |
| FR-M01 | M6 | US-601 AC 1,2 | businesses, users, branches, customers, transactions, loyalty_histories; semua fixture domain | DemoProvisioner | AND-12, ISO-01 |
| FR-M02 | M6 | US-601 AC 1,3 | businesses, users, branches, customers, transactions, loyalty_histories; semua fixture domain | DemoProvisioner | AND-12, ISO-01 |
| FR-M03 | M6 | US-602 AC 1,2,3,4 | users, branches, businesses, sessions | DemoProvisioner; role switch arsitektur2/7 | AND-12, ISO-02 |
| FR-M04 | M6 | US-603 AC 1,2 | notification_logs, businesses.is_demo | NotificationDispatcher; transport guard arsitektur 6.4 | AND-12, AND-25 |
| FR-M05 | M6 | US-604 AC 1,3 | businesses.demo_expires_at; seluruh child/infra demo pada skema3/5 | DemoPurgeService | DAT-02, AND-12, SEC-02 |
| FR-M06 | M6 | US-604 AC 2 | businesses.demo_expires_at; seluruh child/infra demo pada skema3/5 | DemoPurgeService | DAT-02, AND-12, SEC-02 |
| FR-L01 | M4 | US-401 AC 1,2,3,4,5 | loyalty_settings, master_services | StampService | AND-16, AND-23 |
| FR-L02 | M4 | US-402 AC 1,2,3,5,6,7 | loyalty_histories, customers.stamp_count, transactions | StampService; CancellationService | AND-02, AND-07, AND-16, AND-23 |
| FR-L03 | M4 | US-403 AC 1,2,3,4,5,6,7; US-402 AC 4 | loyalty_settings, loyalty_histories, transactions.stamp_reward_max_kg_snapshot, transaction_items; loyalty_histories, customers.stamp_count, transactions | StampService; PricingService; StampService; CancellationService | AND-07, AND-16, AND-22, AND-02, AND-23 |
| FR-L04 | M4 | US-404 AC 1,2; US-402 AC 4,5,7 | loyalty_histories, customers.stamp_count; loyalty_histories, customers.stamp_count, transactions | StampService; scoped customer route; StampService; CancellationService | AND-23, ISO-02, AND-02, AND-07, AND-16 |
| FR-P01 | M4 | US-406 AC 1,2 | promos, promo_branches | PricingService; owner policy arsitektur3 | AND-08, AND-22, ISO-06 |
| FR-P02 | M4 | US-407 AC 1,2,3,5,6 | transactions, promos, promo_branches, transaction_items | PricingService | AND-08, AND-22 |
| FR-P03 | M4 | US-407 AC 1,4 | transactions, promos, promo_branches, transaction_items | PricingService | AND-08, AND-22 |
| FR-N01 | M3 | US-301 AC 1,2,3,4,5; US-308 AC 10,11,12,13 | transactions.waktu_siap_diambil, notification_logs.created_at, jobs; hold/cutoff konfigurasi deployment | NotificationDispatcher; restore arsitektur9 | AND-04, AND-10, AND-21, AND-05, AND-27 |
| FR-N02 | M3 | US-302 AC 1,2,3,4,5,6,7,8,9,10; US-308 AC 11,12,13 | business_settings, transactions.reminder_count, transactions.last_reminder_at, transactions.waktu_siap_diambil, notification_logs.created_at, jobs | NotificationDispatcher; scheduler arsitektur7; restore arsitektur9 | AND-04, AND-20, AND-21, AND-27, AND-28 |
| FR-N03 | M3 | US-307 AC 1,2,8,9,10; US-305 AC 2,3,4; US-213 AC 5,6,7,8,9; US-214 AC 6,7,8,9 | businesses, business_settings, notification_logs.tujuan, notification_logs.delivery_started_at, notification_logs.attempt_count, jobs; businesses.wa_config, businesses.wa_token, businesses.smtp_config | WhatsAppProvider; NotificationDispatcher; recipient arsitektur6.2.1; adapter arsitektur6.3; restore arsitektur9 | AND-11, AND-27, AND-28, SEC-03, ISO-05 |
| FR-N04 | M3 | US-307 AC 3,4,5,6,7,9; US-306 AC 2 | businesses, business_settings, notification_logs, jobs, notification_logs.wa_quota_month | WhatsAppProvider; NotificationDispatcher; kuota arsitektur6.3 | AND-11, AND-27, AND-28, AND-20 |
| FR-N05 | M3 | US-308 AC 1,3,4,5,10,11,12,13; US-213 AC 6; US-214 AC 8 | notification_logs, jobs, failed_jobs | NotificationRecoveryService; recipient arsitektur6.2.1; restore arsitektur9 | AND-03, AND-04, AND-10, AND-27, AND-28, ISO-03 |
| FR-N06 | M3 | US-308 AC 2,4,6,7,8,9,10,11,12,13; US-307 AC 9 | notification_logs, jobs, failed_jobs; hold/cutoff konfigurasi deployment | NotificationRecoveryService; recipient arsitektur6.2.1; restore arsitektur9 | AND-03, AND-04, AND-10, AND-05, AND-27, AND-28, ISO-03 |
| FR-R01 | M2 | US-203 AC 5; US-209 AC 1 | transactions, transaction_items, customers, services, payments, status_histories; transactions, transaction_items, payments, branches, customers; QR derived | TransactionService; PricingService; ReceiptCodeGenerator; Route cetak arsitektur8; ReceiptCodeGenerator | AND-01, AND-08, AND-18, AND-22, SEC-06, KOM-02 |
| FR-R02 | M2 | US-209 AC 1,2 | transactions, transaction_items, payments, branches, customers; QR derived | Route cetak arsitektur8; ReceiptCodeGenerator | SEC-06, AND-08, KOM-02 |
| FR-R03 | M2 | US-209 AC 1,4 | transactions, transaction_items, payments, branches, customers; QR derived | Route cetak arsitektur8; ReceiptCodeGenerator | SEC-06, AND-08, KOM-02 |
| FR-R04 | M2 | US-209 AC 2,3,4 | transactions, transaction_items, payments, branches, customers; QR derived | Route cetak arsitektur8; ReceiptCodeGenerator | SEC-06, AND-08, KOM-02 |
| FR-W01 | M6 | US-605 AC 1 | Tanpa tabel; manifest/aset statis | PWA arsitektur8 | KOM-03, AND-26 |
| FR-W02 | M6 | US-606 AC 1,2,3,4 | Tanpa tabel; SW/cache statis | PWA arsitektur8 | AND-26 |

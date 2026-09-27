# Kebutuhan Non-Fungsional (NFR) — CekLaundry

> **Kedudukan dokumen:** perluasan dari `prd.md` Bagian 12 menjadi kebutuhan terukur ber-ID. Jika ada pertentangan, `prd.md` menang. Setiap NFR wajib dipenuhi; NFR bertanda **[Uji]** wajib punya pengujian otomatis, bertanda **[Ops]** dipenuhi lewat konfigurasi/prosedur operasional.

---

## 1. Kinerja (KIN)

| ID | Kebutuhan |
|---|---|
| KIN-01 | Halaman publik cek status tampil lengkap < 3 detik pada koneksi seluler biasa (acuan uji: throttling "Fast 3G/4G" di devtools). |
| KIN-02 | Total berat transfer halaman publik (depan, status, resi) ≤ 200 KB per halaman (HTML+CSS+JS+font, sebelum gambar QR) — halaman publik tidak boleh memuat bundle panel React. |
| KIN-03 | Dashboard admin & owner interaktif < 2,5 detik pada koneksi yang sama setelah login (muatan awal panel boleh lebih berat, navigasi antarhalaman Inertia harus terasa instan). |
| KIN-04 | **[Uji]** Tidak ada masalah kueri N+1 pada daftar transaksi, dashboard, dan laporan; semua kueri daftar/laporan memakai indeks yang didefinisikan `database-schema.md`. |
| KIN-05 | Alur "Transaksi Baru" dapat diselesaikan admin dalam < 1 menit untuk pelanggan lama (metrik desain: pencarian pelanggan instan, tambah item tanpa pindah halaman). |

## 2. Keamanan (SEC)

| ID | Kebutuhan |
|---|---|
| SEC-01 | Password di-hash dengan algoritma standar Laravel (bcrypt/argon2id); tidak ada password tersimpan/terlog dalam bentuk asli. |
| SEC-02 | **[Uji]** Rate limit: pengecekan kode resi maks 30 permintaan/menit/IP; percobaan login maks 5/menit/akun+IP; pembuatan demo maks 3/hari/IP. Melebihi batas → respons 429 dengan pesan ramah. |
| SEC-03 | Kredensial WA (`wa_token`) dan `smtp_config` tersimpan terenkripsi (encrypted cast); tidak pernah dikirim ke frontend selain form developer, dan tidak pernah tertulis di log. |
| SEC-04 | Seluruh form panel terlindungi CSRF; sesi memakai cookie `HttpOnly` + `Secure`; aplikasi hanya dilayani lewat HTTPS di produksi (dipaksa di reverse proxy). |
| SEC-05 | **[Uji]** Otorisasi berbasis Policy untuk setiap resource; akses lintas peran ditolak (mis. admin membuka route owner → 403). |
| SEC-06 | **[Uji]** Data pelanggan pada halaman publik selalu tersamar (FR-C06); respons `/t/{kode}` tidak memuat email, no. HP utuh, atau ID internal. |
| SEC-07 | Aksi berisiko (gabung pelanggan, sinkronisasi master, perubahan masa aktif, reset password, pembatalan transaksi) tercatat di `audit_logs` beserta pelakunya. |
| SEC-08 | **[Uji]** Mode baca-saja menolak seluruh tulis bisnis tenant di server dengan 423, termasuk POST email publik FR-C04; GET status publik tetap tersedia. Logout, ganti password awal, dan alur lupa/reset password tetap diizinkan sebagai operasi keamanan akun. Bisnis `NONAKTIF` juga menolak POST email publik walau GET status tersedia. |
| SEC-09 | **[Uji]** Kode resi saja tidak boleh mengubah `customers.email` atau email notifikasi aktif. FR-C04 memakai tautan bertanda tangan sekali pakai 24 jam dan versi terbaru; GET hanya menampilkan konfirmasi, POST sah pada `DITERIMA`/`DIPROSES` yang mengubah data. Permintaan dibatasi 3 kali/jam per kode resi dan 10 kali/hari per IP. Tautan lama, kedaluwarsa, terpakai, atau dikonfirmasi setelah status siap/batal, merge sumber, atau tenant tidak dapat menulis ditolak. Verifikasi membuktikan kontrol email baru, bukan identitas pemilik customer. |

## 3. Isolasi Multi-Tenant (ISO) — paling kritis

| ID | Kebutuhan |
|---|---|
| ISO-01 | **[Uji]** Model dengan kolom `business_id` langsung memakai `BelongsToBusiness`; child tanpa kolom tersebut hanya diakses melalui parent terscope. User bisnis A memperoleh 404/daftar-kosong untuk resource bisnis B (transaksi, pelanggan, laporan, pengaturan, ekspor). |
| ISO-02 | **[Uji]** Admin cabang 1 memperoleh 404/daftar-kosong untuk data cabang 2 pada bisnis yang sama, termasuk child transaksi dan tindakan tulis; owner hanya pada `business_id` miliknya. |
| ISO-03 | **[Uji]** Developer tidak dapat membuka/mencari transaksi, pelanggan, pembayaran, atau detail operasional bisnis mana pun. Pengecualian hanya endpoint statistik agregat tenant yang eksplisit pada FR-D04: jumlah cabang dan jumlah transaksi 30 hari; respons hanya angka tanpa ID/baris individual. |
| ISO-04 | Akses lintas tenant merespons **404**, bukan 403, agar keberadaan data tidak bocor. |
| ISO-05 | Ekspor CSV, pencarian, dan pembuat laporan tunduk pada scope yang sama (tidak ada jalur pintas kueri mentah tanpa filter tenant). |
| ISO-06 | **[Uji]** `services.business_id = branches.business_id`; `transactions.business_id = branches.business_id = customers.business_id`; `payments`, `status_histories`, `notification_logs`, `loyalty_histories` cocok dengan tenant parent. FK komposit dan service menolak ID lintas tenant; `users.branch_id`, pivot promo, dan hadiah loyalti juga divalidasi tenant/cabang. Tenant tidak diambil dari URL/input pengguna. |

## 4. Keandalan & Integritas (AND)

| ID | Kebutuhan |
|---|---|
| AND-01 | Operasi tulis multi-langkah (buat transaksi+item+pembayaran, pembatalan, penggabungan pelanggan, sinkronisasi master, penukaran stempel) dibungkus transaksi database — sukses seluruhnya atau gagal seluruhnya. |
| AND-02 | **[Uji]** Invarian: kumulatif `payments` ≤ `total_akhir`; untuk total Rp0 status `LUNAS` tanpa payment Rp0, selebihnya `BELUM_BAYAR`/`DP`/`LUNAS` sesuai jumlah bayar; `customers.stamp_count = SUM(loyalty_histories.jumlah)` termasuk kompensasi pembatalan dan merge. |
| AND-03 | Job notifikasi dicoba ulang maksimal 3 kali dengan backoff; kegagalan akhir tercatat (`notification_logs` = `gagal`, baris `failed_jobs` tersimpan) tanpa memengaruhi alur utama. |
| AND-04 | **[Uji]** Pengingat memakai key unik (`transaction_id`, tipe, kanal, nomor pengingat); scheduler/job/retry berulang tidak menggandakan kanal yang sukses. Pembersihan demo idempoten. Hasil penyedia yang tidak pasti tidak diulang buta tanpa dukungan idempotensi penyedia. |
| AND-05 | **[Ops]** Backup otomatis harian database + penyimpanan minimal 7 hari; prosedur pemulihan terdokumentasi. |
| AND-06 | **[Uji]** Dua pembayaran paralel pada sisa sama memakai lock transaksi: hanya pembayaran yang muat pada sisa berhasil, tak ada overpay, status bayar tetap benar; edit harga `DITERIMA` bersamaan dengan pembayaran juga tidak merusak total. |
| AND-07 | **[Uji]** Dua penukaran paralel memakai lock customer: saldo dicek setelah lock; hanya penukaran yang cukup saldo berhasil dan penukaran tidak membuat saldo negatif. Pembatalan mengembalikan jumlah stempel aktual tanpa memakai N terkini. |
| AND-08 | **[Uji]** Snapshot item (nama/satuan/harga/subtotal final), promo (nama/tipe/nilai/potongan), dan email transaksi tetap stabil setelah master/customer berubah. Promo minimum dihitung atas `subtotal - potongan_stempel`; nominal dibatasi basis dan total ≥ 0. |
| AND-09 | **[Uji]** Edit harga hanya untuk `DITERIMA` tanpa payment **dan tanpa loyalty history apa pun**; transaksi Rp0 `LUNAS` yang memperoleh stempel tetap terkunci. Catatan/estimasi boleh berubah pada `DITERIMA`; sejak `DIPROSES` edit operasional ditolak. FR-C04 hanya mengubah email aktif setelah verifikasi sah sebelum siap diambil. Pembatalan dan merge menjaga delta historis. |
| AND-10 | **[Uji]** Reservasi log notifikasi dan insert job database queue atomik pada koneksi/transaksi yang sama; crash sebelum commit tidak meninggalkan log/job tunggal. Claim worker memakai token dan lease 5 menit; stale sebelum pemanggilan penyedia dapat direclaim tepat sekali, sedangkan stale setelah pemanggilan dimulai menjadi `perlu_pemeriksaan` kecuali penyedia mendukung idempotency key. |
| AND-11 | **[Uji]** Dua reservasi WA paralel pada 99/100 slot hanya mengizinkan satu; hitungan slot mencakup `tertunda`/`diproses`/`berhasil`/`perlu_pemeriksaan` di bulan WIB yang sama. Job yang melintasi pergantian bulan memesan ulang slot sebelum kirim. Penghitung owner hanya menghitung `berhasil` menurut `sent_at`. |
| AND-12 | **[Uji]** Mode demo membuat satu log `ditekan_demo` per kanal yang eligible dengan `kanal` dan `tujuan` terisi, tanpa pengiriman nyata; pergantian ke admin demo selalu memakai admin cabang pertama dan tidak membuka cabang kedua. Pembatalan menulis pelaku/alasan ke `audit_logs` dalam transaksi DB yang sama. |

## 5. Usabilitas & Aksesibilitas (UX)

| ID | Kebutuhan |
|---|---|
| UX-01 | Panel owner mengikuti seluruh aturan `prd.md` 12.2: teks dasar ≥ 16px, maksimal 5–6 menu utama, dashboard kartu angka besar, satu aksi utama per halaman, istilah sehari-hari tanpa jargon, konfirmasi eksplisit untuk aksi berisiko. |
| UX-02 | Kontras teks memenuhi rasio minimal 4,5:1 (setara WCAG AA); target sentuh tombol ≥ 44×44 px pada tampilan HP. |
| UX-03 | Seluruh alur admin (transaksi baru, update status, pembayaran, cetak) dapat dikerjakan penuh dari layar HP tanpa scroll horizontal. |
| UX-04 | Setiap aksi tulis memberi umpan balik jelas (berhasil/gagal) dalam bahasa Indonesia; pesan error tidak menampilkan detail teknis (stack trace, nama kolom). |
| UX-05 | Halaman publik dapat dipakai tanpa JavaScript untuk fungsi inti pengecekan status (form submit biasa) — JS hanya peningkatan. |
| UX-06 | **[Uji]** Jika ledger stempel negatif akibat pembatalan perolehan yang sudah dipakai, halaman status menampilkan nilai bertanda sebenarnya (mis. `−1/10`) dengan penjelasan bahwa stempel perlu diperoleh kembali; jangan menyamarkan sebagai nol. |

## 6. Kompatibilitas (KOM)

| ID | Kebutuhan |
|---|---|
| KOM-01 | Didukung: Chrome/Edge/Firefox/Safari rilis dua tahun terakhir; Android (Chrome/WebView) dan iOS (Safari) untuk HP. |
| KOM-02 | Cetak resi teruji rapi pada printer thermal 58 mm melalui dialog print browser (lebar cetak ±48 mm), termasuk dari Android. QR tetap terpindai pada hasil cetak. |
| KOM-03 | PWA terpasang benar di Android dan iOS; keterbatasan iOS didokumentasikan dan tidak ada fitur yang bergantung pada push PWA (lihat `prd.md` Bagian 14). |

## 7. Lokalisasi (LOK)

| ID | Kebutuhan |
|---|---|
| LOK-01 | Seluruh antarmuka, email, dan pesan berbahasa Indonesia. |
| LOK-02 | Uang selalu ditampilkan `Rp12.500` (titik ribuan, tanpa desimal); berat `3,5 kg` (koma desimal). |
| LOK-03 | Seluruh waktu memakai Asia/Jakarta (WIB); format tanggal tampil "8 Juli 2026", waktu "14.30 WIB". |
| LOK-04 | Nomor HP disimpan format internasional `62…`, ditampilkan `0812-…` seperlunya; input admin menerima `08…` dan dinormalisasi otomatis. |

## 8. Pemeliharaan & Kualitas Kode (PLH)

| ID | Kebutuhan |
|---|---|
| PLH-01 | TypeScript mode `strict` untuk seluruh kode React; PHP mengikuti pemformatan Laravel Pint; ESLint untuk frontend — semuanya dijalankan di CI. |
| PLH-02 | **[Uji]** Seluruh aturan bisnis `prd.md` Bagian 7 dan kriteria Bagian 16 memiliki pengujian otomatis (unit/feature) dan wajib lulus di CI sebelum deploy. |
| PLH-03 | Migrasi database menjadi satu-satunya sumber skema; seeder tersedia untuk: akun developer awal, dan data contoh demo (dipakai `DemoProvisioner`). |
| PLH-04 | Konfigurasi lewat variabel lingkungan; tidak ada kredensial di repository. |

## 9. Observabilitas (OBS)

| ID | Kebutuhan |
|---|---|
| OBS-01 | Log aplikasi terstruktur (per hari) untuk error & peristiwa penting; `failed_jobs` dapat dipantau developer. |
| OBS-02 | Seluruh upaya notifikasi terekam di `notification_logs` dan terlihat di detail transaksi (FR-N05); penghitung WA bulanan owner hanya dari kanal `whatsapp` API berstatus `berhasil` dengan `sent_at` pada bulan WIB berjalan. Pembukaan `wa.me` memakai `whatsapp_manual`/`dibuka_manual` dan tidak dihitung terkirim. |
| OBS-03 | Kesalahan server memberi halaman error ramah kepada pengguna; detail teknis hanya masuk log. |

## 10. Data, Retensi & Kapasitas (DAT)

| ID | Kebutuhan |
|---|---|
| DAT-01 | `payments`, `status_histories`, `loyalty_histories`, dan `audit_logs` bersifat tambah-saja dan tidak pernah dihapus selama bisnis ada (kecuali pembersihan tenant demo); master yang dipakai transaksi dinonaktifkan dan snapshot histori dipertahankan. |
| DAT-02 | Data tenant demo terhapus tuntas ≤ 7 hari setelah dibuat (FR-M05) — mencakup seluruh tabel terkait. |
| DAT-03 | `notification_logs` disimpan minimal 12 bulan. |
| DAT-04 | Asumsi kapasitas desain (bukan batas keras): hingga ±200 bisnis aktif, ±50.000 transaksi/bulan agregat, berjalan nyaman pada satu VPS (2–4 vCPU, 4–8 GB RAM). Desain kueri & indeks mengacu pada angka ini. |
| DAT-05 | Tidak ada data pelanggan yang dipakai lintas bisnis; tidak ada pelacakan/analitik pihak ketiga di halaman publik. |

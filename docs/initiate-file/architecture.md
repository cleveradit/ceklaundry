# Dokumen Arsitektur & Teknologi — CekLaundry

> **Kedudukan dokumen:** turunan teknis dari `prd.md`. Jika ada pertentangan, `prd.md` menang. Dokumen ini menetapkan keputusan implementasi agar agentic AI tidak perlu menebak. ID kebutuhan (`FR-…`) merujuk ke `prd.md`; ID NFR merujuk ke `nfr.md`.

---

## 1. Ringkasan & Prinsip Arsitektur

Aplikasi monolit Laravel dengan satu database MySQL, melayani banyak bisnis laundry (multi-tenant baris-per-baris via kolom `business_id`). Tiga panel ber-login (developer, owner, admin) dibangun dengan Inertia + React; halaman publik (halaman depan, cek status, resi) dibangun sebagai Blade ringan terpisah dari bundle panel.

Prinsip yang wajib dipegang:

1. **Isolasi tenant di lapisan data**, bukan hanya UI — model dengan `business_id` langsung memakai scope tenant; child tanpa kolom itu wajib diakses melalui parent terscope (Bagian 5).
2. **Aturan bisnis di lapisan service**, bukan di controller/komponen React — controller tipis, React hanya presentasi.
3. **Server adalah penegak terakhir** — pembatasan (baca-saja, kunci transaksi, batas WA) selalu divalidasi di server; penonaktifan tombol di UI hanyalah kenyamanan.
4. **Tanpa infrastruktur tambahan** — queue memakai driver database, penjadwal memakai cron; tidak ada Redis, tidak ada layanan pihak ketiga selain SMTP & penyedia WA.

## 2. Stack (Keputusan Final)

| Lapisan | Teknologi |
|---|---|
| Bahasa & framework | PHP 8.3+, Laravel 12 |
| Database | MySQL 8 |
| Panel (developer/owner/admin) | Inertia.js + React (TypeScript, mode strict) + Tailwind CSS + shadcn/ui, build via Vite |
| Halaman publik | Blade + CSS ringan + JavaScript vanila seperlunya (tanpa bundle React) |
| Queue | Laravel queue, driver `database` |
| Penjadwal | Laravel scheduler via cron (`schedule:run` tiap menit) |
| QR code | Paket pembuat QR sisi server (mis. `simplesoftwareio/simple-qrcode`), dirender inline (SVG) di halaman resi |
| PWA | Web app manifest + service worker buatan sendiri (FR-W01–W02) |
| Deployment | Docker Compose di VPS, di belakang reverse proxy (mis. Nginx Proxy Manager) |
| CI/CD | GitHub Actions |

Keputusan tambahan: **tidak memakai SSR Inertia** (panel berada di balik login; halaman publik sudah Blade murni, jadi SSR tidak memberi manfaat). Basis autentikasi memakai starter kit resmi Laravel (React), disesuaikan.

## 3. Struktur Aplikasi & Routing

Empat kelompok route dengan middleware berbeda:

| Kelompok | Prefix | Middleware inti | Isi |
|---|---|---|---|
| Publik baca | `/`, `GET /t/{kode_resi}` | `throttle:status-check` untuk pengecekan kode | Halaman depan dan status tetap tersedia meski tenant baca-saja/nonaktif |
| Publik tulis | POST permintaan/konfirmasi email halaman status | `throttle`, resolusi transaksi dari kode, `lifecycle:public-write` | FR-C04; verifikasi email sebelum mengubah data aktif; server menolak saat tenant `BACA_SAJA`/`NONAKTIF` |
| Publik demo | POST Coba Demo | rate limit demo | FR-M01, membuat tenant demo baru |
| Panel Admin | `/app` | `auth`, `role:admin,owner`, `tenant`, `lifecycle` | Dashboard, transaksi, pelanggan, resi |
| Panel Owner | `/owner` | `auth`, `role:owner`, `tenant`, `lifecycle` | Laporan, cabang, admin, layanan master, promo, stempel, pengaturan perilaku |
| Panel Developer | `/dev` | `auth`, `role:developer` | Daftar bisnis, masa aktif, konfigurasi teknis |

Owner mengakses fungsi operasional lewat kelompok `/app` juga (FR-O06) dengan pemilih cabang.

## 4. Autentikasi & Otorisasi

- Satu tabel `users` dengan kolom `role` (`developer` / `owner` / `admin`). Email unik global.
- Kolom `must_change_password` memaksa penggantian password saat login pertama (FR-D02, FR-O01).
- Otorisasi memakai Laravel Policy per model; admin dibatasi `branch_id`, owner dibatasi `business_id`. Developer tidak memiliki route/policy untuk membaca baris transaksi, pelanggan, pembayaran, atau detail operasional; endpoint daftar bisnis hanya boleh mengembalikan angka agregat jumlah cabang dan transaksi 30 hari (FR-D04), tanpa ID/baris individual.

## 5. Multi-Tenancy

- `BelongsToBusiness` dipakai pada `BusinessSetting`, `Branch`, `MasterService`, `Service`, `Customer`, `Transaction`, `Payment`, `Promo`, `LoyaltySetting`, `LoyaltyHistory`, `StatusHistory`, dan `NotificationLog`; semua tabelnya mempunyai `business_id` langsung. Trait menerapkan `where business_id = tenant aktif` dan mengisi tenant saat create. `User` owner/admin dibatasi bisnis melalui autentikasi/policy; `AuditLog` memakai filter bisnis eksplisit karena aksi developer lintas tenant boleh `business_id=null`. `Business` adalah tenant root.
- `TransactionItem` mengambil tenant hanya dari `Transaction` parent; `PromoBranch` dari `Promo`/`Branch` parent. Tidak boleh ada query child bebas tanpa join/parent yang sudah terscope.
- Tenant aktif hanya dari user login (`$user->business_id`), bukan parameter URL/input. Untuk job, tenant berasal dari baris parent yang tersimpan dan diverifikasi ulang; untuk halaman publik hanya dari transaksi yang ditemukan melalui kode resi global.
- Pengecualian internal yang terpercaya: `DemoProvisioner` dan service pendaftaran bisnis developer membuat `Business` dulu, lalu mengisi `business_id` anak dari ID bisnis **yang baru dibuat** secara eksplisit dengan bypass scope terbatas di service tersebut. Statistik FR-D04 memakai kueri agregat internal. Route publik mencari transaksi hanya lewat kode resi global, kemudian memakai `business_id`/`customer_id` dari baris transaksi tersimpan untuk memulai atau mengonfirmasi FR-C04; POST tidak menerima `business_id`/`customer_id` dari form. Bypass ini tidak tersedia pada controller operasional umum.
- `Service` dan `Transaction` juga difilter `branch_id` untuk admin memakai `ForUserBranch`; `TransactionItem`, `Payment`, `StatusHistory`, dan `NotificationLog` mengikuti cabang transaksi parent. Customer dan ledger lintas cabang dalam bisnis hanya diakses dalam konteks operasi/cabang yang diizinkan; owner bebas seluruh cabangnya.
- Saat menulis, service memverifikasi kesamaan tenant pada cabang, layanan, customer, transaksi, promo, payment, status, notifikasi, dan ledger; FK komposit yang tersedia menguatkan relasi ini (lihat skema). Admin tidak boleh menyisipkan `branch_id` lain.
- Route model binding wajib melewati scope ini — pengambilan record via ID milik bisnis lain menghasilkan 404, bukan 403 (tidak membocorkan keberadaan data).
- Halaman publik `/t/{kode}` adalah satu-satunya jalur tanpa tenant: pencarian berdasarkan `kode_resi` unik global, hanya menampilkan data yang ditentukan FR-C03 dengan penyamaran FR-C06.
- **Pengujian isolasi wajib** (NFR): test otomatis yang memastikan user bisnis A mendapat 404/kosong untuk seluruh resource bisnis B, dan admin cabang 1 untuk data cabang 2.

## 6. Siklus Hidup Bisnis (Masa Aktif) — FR-D05

Status turunan bisnis dihitung dari `active_until` (tidak disimpan sebagai kolom status):

```
AKTIF        : hari_ini ≤ active_until
TENGGANG     : active_until < hari_ini ≤ active_until + 7 hari
BACA_SAJA    : hari_ini > active_until + 7 hari
NONAKTIF     : is_active = false (dimatikan developer, menolak login)
DEMO         : is_demo = true (abaikan active_until; pakai demo_expires_at)
```

Middleware `lifecycle`:

- `AKTIF` dalam 7 hari sebelum `active_until` → kirim flag banner peringatan ke Inertia (shared props).
- `TENGGANG` → semua fungsi berjalan + flag banner mencolok.
- `BACA_SAJA` → `GET`/`HEAD` panel diizinkan; tulis **bisnis tenant** (`POST`/`PUT`/`PATCH`/`DELETE`) ditolak 423. Pengecualian operasi keamanan akun: logout, POST ganti password awal, dan alur lupa/reset password (permintaan serta penyelesaian reset); endpoint ini tetap tersedia agar `must_change_password` tidak mengunci akun. Notifikasi otomatis tidak dijadwalkan/dikirim; frontend menonaktifkan aksi bisnis.
- `GET /t/{kode_resi}` selalu tersedia termasuk untuk bisnis `NONAKTIF`. POST permintaan **dan** konfirmasi email FR-C04 melewati resolusi transaksi + pemeriksaan lifecycle tenant pemilik transaksi dan ditolak 423 bila `BACA_SAJA`/`NONAKTIF`; form disembunyikan/dinonaktifkan. Jangan memakai bypass route baca untuk POST ini.

## 7. Lapisan Domain (Service Layer)

Semua aturan bisnis `prd.md` Bagian 7 hidup di service berikut (controller hanya memanggil):

| Service | Tanggung jawab | Rujukan |
|---|---|---|
| `ReceiptCodeGenerator` | Kode 6 karakter dari alfabet `ABCDEFGHJKMNPQRSTUVWXYZ23456789` (tanpa O/0/I/1/L; 31⁶ ≈ 887 juta kombinasi), coba-ulang saat tabrakan unik | FR-R01 |
| `PricingService` | Snapshot nama/satuan/harga/subtotal item; potongan stempel → `promo_eligible_base` → validasi `minimal_total` → promo → total ≥ 0 | 7.1–7.2 |
| `EstimationService` | `waktu_masuk + durasi_terlama`; boleh dikoreksi manual | 7.3 |
| `TransactionStateMachine` | Peta transisi sah (maju satu arah + pembatalan), pencatatan `status_histories`, kolom waktu khusus, pemicu event | Bagian 6 |
| `PaymentService` | Dalam DB transaction: lock transaksi `FOR UPDATE`, hitung ulang pembayaran/sisa, validasi, insert payment, turunkan status (`total_akhir=0` → `LUNAS`), proses perolehan stempel, commit | 7.4 |
| `CancellationService` | Batalkan + alasan, keluarkan pendapatan; tambah `pengembalian_penukaran` dan/atau `pencabutan_perolehan` sesuai ledger asal; tulis pelaku/alasan ke `audit_logs` dalam transaksi DB yang sama | FR-A15, 7.6 |
| `StampService` | Lock customer `FOR UPDATE`, periksa saldo ledger/cache, simpan delta stempel aktual bertanda secara append-only dan perbarui cache atomik; matematika hadiah `min(berat, maks)` | FR-L01–L04 |
| `MasterSyncService` | Pratinjau & eksekusi "Salin/Perbarui dari Master" dan "Sebarkan ke Cabang": tambah-baru, timpa-nama-sama, biarkan-khusus-cabang | FR-O05 |
| `CustomerMergeService` | Lock source/target satu bisnis, batalkan semua verifikasi email pending transaksi source, pindah transaksi + ledger, pertahankan identitas target, hitung ulang cache, hapus source, catat audit dalam satu DB transaction | FR-A14 |
| `EmailVerificationService` | FR-C04: simpan email pending + versi terbaru, antre tautan 24 jam, validasi tanda tangan/status/lifecycle, lalu perbarui email transaksi dan customer atomik | FR-C04 |
| `NotificationDispatcher` | Alur keputusan kanal (Bagian 8 di bawah) | FR-N01–N06 |
| `DemoProvisioner` | Buat tenant demo + seed data contoh dan satu admin demo di cabang pertama + login owner otomatis; rate limit per IP | FR-M01–M06 |
| `RevenueReportService` | Pendapatan berbasis tanggal pembayaran, kecualikan transaksi batal; tagihan berjalan; kartu menumpuk `SIAP_DIAMBIL` dengan `waktu_siap_diambil <= waktu_sekarang - reminder_first_days hari` dalam WIB | 7.5, FR-O13 |

Semua operasi tulis multi-langkah (buat transaksi + item + pembayaran; pembatalan; penggabungan; sinkronisasi master) dibungkus transaksi database (atomik).

Edit harga `DITERIMA` juga mengunci transaksi, mengecek ulang belum ada payment **dan belum ada `loyalty_histories` jenis apa pun** untuk transaksi itu, lalu menghitung ulang total dan status bayar. Edit catatan/estimasi tetap sah di `DITERIMA`. Sejak `DIPROSES`, hanya konfirmasi email FR-C04 yang boleh mengubah `notification_email` dan `customers.email` sebelum `SIAP_DIAMBIL`; payload endpoint itu tidak menerima field operasional. Permintaan publik lebih dahulu hanya mengisi email pending dan versi verifikasi pada transaksi, bukan email aktif. Transaksi Rp0 menjadi `LUNAS` tanpa payment dan memicu perolehan stempel bila program aktif serta tidak memakai penukaran; entry +1 tersebut mengunci edit harga. Saat pembatalan perolehan lama menyebabkan saldo negatif, cache saldo memakai integer bertanda; penukaran baru tetap wajib melihat saldo cukup sesudah lock.

## 8. Arsitektur Notifikasi

Alur keputusan saat peristiwa terjadi (status → `SIAP_DIAMBIL`, atau pengingat jatuh tempo):

```
peristiwa
 ├─ bisnis BACA_SAJA/NONAKTIF? → jangan kirim otomatis
 ├─ tentukan kanal eligible: email bila notification_email ada;
 │   WA bila token/nomor/saklar peristiwa aktif dan batas bulan tersedia
 ├─ bisnis demo? → satu log 'ditekan_demo' per kanal eligible (kanal+tujuan terisi), tanpa kirim
 └─ bisnis nyata → reservasi log+job email dan/atau slot WA secara atomik;
                  jika slot WA penuh, log 'dilewati_batas' dan email tetap berjalan
```

- **Adapter WA:** kontrak `WhatsAppProvider` dengan driver `FonnteProvider`, `WablasProvider`, `WabaProvider`. Kredensial per bisnis (kolom terenkripsi di `businesses`), dikonfigurasi hanya oleh developer (FR-D06). Jangan mengikat kode ke satu vendor.
- **Kuota WA bulanan:** saat mereservasi kanal WA, lock baris `businesses` dengan `FOR UPDATE`. Dalam transaksi yang sama hitung slot bulan WIB berjalan pada `notification_logs` berkanal `whatsapp` dengan status `tertunda`, `diproses`, `berhasil`, atau `perlu_pemeriksaan`; hanya jika jumlah < `wa_monthly_limit`, isi `wa_quota_month`, buat log/job, lalu commit. `gagal` final melepas slot; `perlu_pemeriksaan` tetap memegang slot sampai diselesaikan agar hasil tak pasti tidak melebihi batas. Jika job baru mengirim pada bulan berikutnya, reservasi bulan lama dilepas dan slot bulan baru diperiksa ulang di bawah lock sebelum pemanggilan penyedia. Penghitung owner **pesan berhasil terkirim** memakai `sent_at` bulan berjalan, bukan slot pending. `wa_monthly_limit=null` berarti tanpa batas.
- **Email:** memakai SMTP global aplikasi secara default; nama & alamat pengirim per bisnis; SMTP khusus bisnis (opsional) dipakai bila diisi developer.
- **Reservasi dan enqueue atomik:** perubahan status atau reservasi urutan pengingat, insert `notification_logs` dan insert job ke queue `database` dilakukan dalam **satu transaksi MySQL pada koneksi yang sama**. Dispatch pada jalur ini tidak ditunda ke `after_commit`; worker baru melihat job setelah commit. Jika transaksi rollback, log dan job sama-sama hilang. Kegagalan pengiriman eksternal setelah commit tidak mengubah status transaksi. Email verifikasi FR-C04 juga mereservasi log/job bersama metadata pending dalam satu transaksi.
- **Idempotensi:** `notification_logs.notification_key` unik untuk notifikasi otomatis dari `(transaction_id, tipe, kanal, reminder_number)`, nomor 0 untuk siap-diambil dan 1..K untuk pengingat. Permintaan verifikasi email memakai key tersendiri dari ID transaksi dan versi verifikasi. Worker mengklaim `tertunda → diproses` dengan `processing_token` unik dan `processing_started_at`; sebelum memanggil penyedia, worker harus berhasil mengisi `delivery_started_at` **dan menaikkan `attempt_count`** memakai compare-and-swap pada token yang sama. Claim yang mati sebelum panggilan tidak menghabiskan jatah tiga percobaan. Worker kedua tidak mengirim key yang sedang diklaim. Kegagalan yang pasti mengembalikan key sama ke `tertunda` serta mengosongkan metadata claim/delivery untuk retry, `gagal` setelah percobaan ketiga. Status `berhasil`, `dilewati_batas`, atau `ditekan_demo` terminal. Email dan WA independen; `reminder_count`/`last_reminder_at` menyimpan urutan jadwal, bukan bukti pengiriman.
- **Pemulihan claim:** lease `diproses` adalah 5 menit; worker queue diberi timeout 2 menit dan `retry_after` queue 3 menit. Scanner terjadwal mereclaim log stale **hanya jika `delivery_started_at` masih null**: ganti token dan sisipkan job ulang untuk key yang sama secara atomik, sehingga worker lama gagal compare-and-swap. Bila pemanggilan penyedia mungkin sudah dimulai, gunakan idempotency key yang sama jika didukung penyedia; tanpa itu ubah ke `perlu_pemeriksaan` dan jangan kirim ulang buta. Sukses dicatat hanya setelah penyedia menerima; tidak diklaim ada jaminan exactly-once dari layanan luar. `notified_ready_at` tidak dipakai.
- **Verifikasi email publik:** permintaan FR-C04 menyimpan `pending_notification_email`, menaikkan `email_verification_version`, dan menetapkan kedaluwarsa 24 jam. Email berisi URL bertanda tangan yang mengikat transaksi+versi. GET URL itu hanya menampilkan halaman konfirmasi; POST konfirmasi mengunci transaksi/customer, memeriksa tanda tangan, versi terbaru, masa berlaku, status `DITERIMA`/`DIPROSES`, dan lifecycle writable; baru lalu memindahkan pending ke `notification_email` serta `customers.email` dan menghapus pending. Job verifikasi memeriksa versi/pending terbaru sebelum mengirim; transisi ke `SIAP_DIAMBIL`/pembatalan dan merge customer sumber menghapus pending serta menaikkan versi agar tautan lama gugur. Link lama atau ulang tidak berlaku. Batasi permintaan 3 kali/jam per kode resi **dan** 10 kali/hari per IP.
- Pembukaan `wa.me` dicatat dengan kanal `whatsapp_manual`, tipe `resi`/`pengingat`, status `dibuka_manual`, key otomatis null. Log ini tidak masuk penghitung WA terkirim dan tidak menyatakan pelanggan telah mengirim pesan.
- Semua pengiriman lewat queue; kegagalan tidak menggagalkan perubahan status (FR-N06).

## 9. Queue & Penjadwal

| Pekerjaan | Mekanisme | Jadwal |
|---|---|---|
| Kirim email/WA instan | Job di queue `database`, retry 3x backoff | Saat peristiwa |
| Pengingat "belum diambil" (FR-N02) | Perintah terjadwal yang memindai `SIAP_DIAMBIL` sesuai N/M/K lalu mengantre job kirim | Harian 08.00 WIB |
| Pulihkan claim notifikasi stale | Periksa `diproses` lebih dari 5 menit: reclaim + antre ulang key sama jika belum `delivery_started_at`; jika sudah mulai, gunakan idempotensi penyedia/`perlu_pemeriksaan` | Setiap menit |
| Pembersihan bisnis demo (FR-M05) | Perintah terjadwal, hapus tenant demo kedaluwarsa beserta seluruh datanya | Harian 03.00 WIB |
| Worker queue | `php artisan queue:work database --timeout=120` di container terpisah; `retry_after=180` pada koneksi database queue | Terus-menerus |

## 10. Halaman Publik, Resi & PWA

- Halaman publik dibangun Blade + CSS minimal; anggaran berat halaman ada di `nfr.md`. Tidak memuat bundle panel.
- Resi: satu view dengan dua mode render — layar (menyatu dengan halaman status) dan cetak (`@media print`, lebar 58 mm) (FR-R03–R04). QR berisi URL `/t/{kode}` dirender SVG inline.
- PWA (FR-W01–W02): manifest lengkap; service worker meng-cache aset statis hasil build Vite (nama file sudah ber-hash, sehingga cache busting otomatis per deploy); data/API tidak di-cache; saat offline tampilkan halaman "Anda sedang offline". Cakupan install: panel admin & owner.

## 11. Struktur Direktori (Backend)

Memakai struktur Laravel standar (bukan DDD modular) — paling mudah dinavigasi agentic AI dan cukup untuk ukuran aplikasi ini:

```
app/
  Enums/            (TransactionStatus, PaymentStatus, Role, ...)
  Http/
    Controllers/    (Public/, App/, Owner/, Dev/)
    Middleware/     (ResolveTenant, EnsureLifecycle, EnsureRole, DemoOnly)
    Requests/       (validasi form per aksi)
  Models/
  Policies/
  Services/         (seluruh service Bagian 7)
  Jobs/             (SendEmailNotification, SendWhatsAppNotification)
  Console/Commands/ (SendPickupReminders, PurgeExpiredDemos)
  Notifications/    (template email)
resources/
  js/               (React panel: Pages/, Components/, Layouts/)
  views/            (Blade publik: home, status, resi; email)
```

## 12. Deployment & Lingkungan

Docker Compose dengan service:

| Service | Peran |
|---|---|
| `app` | PHP-FPM (kode aplikasi) |
| `web` | Nginx internal, meneruskan ke `app` |
| `db` | MySQL 8 + volume data |
| `worker` | `queue:work database --timeout=120`, koneksi queue dengan `retry_after=180` |
| `cron` | `schedule:run` tiap menit |

Reverse proxy publik (mis. Nginx Proxy Manager) berada di luar compose ini dan meneruskan HTTPS ke `web`. Variabel lingkungan penting: `APP_URL`, kredensial DB, `QUEUE_CONNECTION=database`, SMTP global, `APP_TIMEZONE=Asia/Jakarta`. Kredensial WA per bisnis **tidak** di `.env` — tersimpan terenkripsi di database (Laravel encrypted cast).

CI/CD (GitHub Actions): lint (Pint + ESLint) → test (PHPUnit/Pest) → build aset Vite → build & push image → deploy ke VPS (SSH/pull). Migrasi dijalankan saat deploy.

## 13. Strategi Pengujian

1. **Isolasi tenant & cabang** — wajib, otomatis (Bagian 5).
2. **Aturan bisnis** — test PricingService (basis minimum promo, snapshot), PaymentService (Rp0, DP, overpay paralel), StampService (ledger bertanda, pembatalan, dua penukaran paralel), edit harga versus catatan, merge customer, dan laporan.
3. **Siklus hidup** — feature test tenggang/baca-saja, pengecualian keamanan akun, GET publik tetap jalan dan POST email ditolak saat baca-saja/nonaktif.
4. **Notifikasi** — test kanal/email snapshot independen, identitas unik per nomor pengingat, reservasi log+job atomik saat crash, lease stale aman, kuota WA paralel, demo per kanal, `wa.me` manual hanya `dibuka_manual`, dan verifikasi email publik.
5. **Demo** — provisioning, isolasi, rate limit, pembersihan.

## 14. Invarian yang Wajib Dijaga (Ringkasan untuk Agentic AI)

1. Model dengan `business_id` memakai scope tenant; child tanpa `business_id` hanya lewat parent terscope. Semua relasi tenant/cabang divalidasi sebelum tulis.
2. Catatan `payments` tidak pernah di-update/delete; tidak ada route untuk itu.
3. Field harga terkunci begitu ada payment atau loyalty history jenis apa pun meski masih `DITERIMA`; sejak `DIPROSES` semua edit operasional ditolak, dengan pengecualian sempit verifikasi email FR-C04 sebelum siap diambil.
4. `SUDAH_DIAMBIL` hanya sah bila `status_bayar = LUNAS` (divalidasi di `TransactionStateMachine`).
5. Status hanya maju sesuai peta transisi; `DIBATALKAN` wajib beralasan.
6. Nama/satuan/harga/subtotal item, nama/tipe/nilai/potongan promo, dan email tujuan notifikasi transaksi di-snapshot.
7. Notifikasi tidak pernah memblokir alur utama (selalu via queue).
8. Server menolak aksi tulis di mode baca-saja meski UI dimanipulasi.
9. `SUM(loyalty_histories.jumlah)` adalah saldo stempel; cache customer harus sama. Pembayaran dan penukaran memakai row lock.
10. `notification_logs` menentukan idempotensi per kanal/peristiwa/nomor pengingat; pembukaan `wa.me` bukan pengiriman sukses.

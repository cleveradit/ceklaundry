# Dokumen Arsitektur & Teknologi — CekLaundry

> **Kedudukan dokumen:** turunan teknis dari `prd.md`. Jika ada pertentangan, `prd.md` menang. Dokumen ini menetapkan keputusan implementasi agar agentic AI tidak perlu menebak. ID kebutuhan (`FR-…`) merujuk ke `prd.md`; ID NFR merujuk ke `nfr.md`.

---

## 1. Ringkasan & Prinsip Arsitektur

Aplikasi monolit Laravel dengan satu database MySQL, melayani banyak bisnis laundry (multi-tenant baris-per-baris via kolom `business_id`). Tiga panel ber-login (developer, owner, admin) dibangun dengan Inertia + React; halaman publik (halaman depan, cek status, resi) dibangun sebagai Blade ringan terpisah dari bundle panel.

Prinsip yang wajib dipegang:

1. **Isolasi tenant di lapisan data**, bukan hanya UI — semua model operasional memakai global scope `business_id` (Bagian 5).
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
| Publik | `/` | `throttle:status-check` untuk pengecekan kode | Halaman depan (FR-C01), `/t/{kode_resi}` (FR-C02), form email (FR-C04), tombol Coba Demo (FR-M01) |
| Panel Admin | `/app` | `auth`, `role:admin,owner`, `tenant`, `lifecycle` | Dashboard, transaksi, pelanggan, resi |
| Panel Owner | `/owner` | `auth`, `role:owner`, `tenant`, `lifecycle` | Laporan, cabang, admin, layanan master, promo, stempel, pengaturan perilaku |
| Panel Developer | `/dev` | `auth`, `role:developer` | Daftar bisnis, masa aktif, konfigurasi teknis |

Owner mengakses fungsi operasional lewat kelompok `/app` juga (FR-O06) dengan pemilih cabang.

## 4. Autentikasi & Otorisasi

- Satu tabel `users` dengan kolom `role` (`developer` / `owner` / `admin`). Email unik global.
- Kolom `must_change_password` memaksa penggantian password saat login pertama (FR-D02, FR-O01).
- Otorisasi memakai Laravel Policy per model; aturan kunci: admin dibatasi `branch_id`-nya, owner dibatasi `business_id`-nya, developer tidak punya akses ke model operasional (transaksi, pelanggan, pembayaran) sama sekali — policy developer hanya mengizinkan model administratif (FR-D04).

## 5. Multi-Tenancy

- Semua model operasional memakai trait `BelongsToBusiness` yang: (a) menambahkan global scope `where business_id = tenant aktif`, (b) mengisi `business_id` otomatis saat membuat record.
- Tenant aktif ditentukan dari user yang login (`$user->business_id`). Tidak ada penentuan tenant dari subdomain/URL.
- Model per-cabang (transaksi, layanan cabang) juga difilter `branch_id` untuk peran admin melalui policy + query scope `ForUserBranch`.
- Route model binding wajib melewati scope ini — pengambilan record via ID milik bisnis lain menghasilkan 404, bukan 403 (tidak membocorkan keberadaan data).
- Halaman publik `/t/{kode}` adalah satu-satunya jalur tanpa tenant: pencarian berdasarkan `kode_resi` unik global, hanya menampilkan data yang ditentukan FR-C03 dengan penyamaran FR-C06.
- **Pengujian isolasi wajib** (NFR): test otomatis yang memastikan user bisnis A mendapat 404/kosong untuk seluruh resource bisnis B, dan admin cabang X untuk data cabang Y.

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
- `BACA_SAJA` → request `GET`/`HEAD` diizinkan; metode tulis (`POST`/`PUT`/`PATCH`/`DELETE`) ditolak dengan status 423 + pesan, **kecuali** logout. Notifikasi otomatis tidak dijadwalkan/dikirim untuk bisnis ini (dicek juga di job). Frontend menonaktifkan tombol aksi berdasarkan shared prop `lifecycle`.
- Halaman publik tidak melewati middleware ini — selalu berfungsi.

## 7. Lapisan Domain (Service Layer)

Semua aturan bisnis `prd.md` Bagian 7 hidup di service berikut (controller hanya memanggil):

| Service | Tanggung jawab | Rujukan |
|---|---|---|
| `ReceiptCodeGenerator` | Kode 6 karakter dari alfabet `ABCDEFGHJKMNPQRSTUVWXYZ23456789` (tanpa O/0/I/1/L; 31⁶ ≈ 887 juta kombinasi), coba-ulang saat tabrakan unik | FR-R01 |
| `PricingService` | Subtotal item (berat minimum, satuan), urutan potongan stempel → promo → total akhir ≥ 0, pembulatan persen ke rupiah | 7.1–7.2 |
| `EstimationService` | `waktu_masuk + durasi_terlama`; boleh dikoreksi manual | 7.3 |
| `TransactionStateMachine` | Peta transisi sah (maju satu arah + pembatalan), pencatatan `status_histories`, kolom waktu khusus, pemicu event | Bagian 6 |
| `PaymentService` | Tambah catatan pembayaran (permanen), tolak jika melebihi sisa, tolak DP saat saklar mati, turunkan `status_bayar`, picu stempel saat `LUNAS` | 7.4 |
| `CancellationService` | Batalkan + alasan, cabut stempel, tandai keluar dari pendapatan | FR-A15, 7.6 |
| `StampService` | Perolehan/penukaran/pencabutan stempel + `loyalty_histories`; matematika penukaran `min(berat, maks)` | FR-L01–L04 |
| `MasterSyncService` | Pratinjau & eksekusi "Salin/Perbarui dari Master" dan "Sebarkan ke Cabang": tambah-baru, timpa-nama-sama, biarkan-khusus-cabang | FR-O05 |
| `CustomerMergeService` | Gabung duplikat: pindahkan transaksi + stempel, hapus sumber, catat ke `audit_logs` | FR-A14 |
| `NotificationDispatcher` | Alur keputusan kanal (Bagian 8 di bawah) | FR-N01–N06 |
| `DemoProvisioner` | Buat tenant demo + seed data contoh + login otomatis; rate limit per IP | FR-M01–M06 |
| `RevenueReportService` | Pendapatan berbasis tanggal pembayaran, kecualikan transaksi batal; tagihan berjalan | 7.5 |

Semua operasi tulis multi-langkah (buat transaksi + item + pembayaran; pembatalan; penggabungan; sinkronisasi master) dibungkus transaksi database (atomik).

## 8. Arsitektur Notifikasi

Alur keputusan saat peristiwa terjadi (status → `SIAP_DIAMBIL`, atau pengingat jatuh tempo):

```
peristiwa
 ├─ bisnis demo?            → catat log 'ditekan_demo', selesai
 ├─ bisnis BACA_SAJA?       → jangan kirim apa pun, selesai
 ├─ EMAIL: pelanggan punya email? → antre kirim email → log berhasil/gagal
 └─ WA:   wa_enabled bisnis?
          └─ saklar peristiwa ini aktif? (wa_on_ready / wa_on_reminder)
             └─ penghitung bulan berjalan < batas bulanan?
                ├─ ya  → antre kirim via adapter → log berhasil/gagal
                └─ tidak → log 'dilewati_batas' (email tetap terkirim)
```

- **Adapter WA:** kontrak `WhatsAppProvider` dengan driver `FonnteProvider`, `WablasProvider`, `WabaProvider`. Kredensial per bisnis (kolom terenkripsi di `businesses`), dikonfigurasi hanya oleh developer (FR-D06). Jangan mengikat kode ke satu vendor.
- **Penghitung bulanan:** dihitung dari `notification_logs` (`kanal = whatsapp`, `status = berhasil`, bulan berjalan, per bisnis) — tidak ada kolom penghitung terpisah.
- **Email:** memakai SMTP global aplikasi secara default; nama & alamat pengirim per bisnis; SMTP khusus bisnis (opsional) dipakai bila diisi developer.
- **Sekali kirim:** kolom `notified_ready_at` pada transaksi mencegah notifikasi "Siap Diambil" ganda; `reminder_count` + `last_reminder_at` mengendalikan pengingat N/M/K.
- Semua pengiriman lewat queue; kegagalan tidak menggagalkan perubahan status (FR-N06).

## 9. Queue & Penjadwal

| Pekerjaan | Mekanisme | Jadwal |
|---|---|---|
| Kirim email/WA instan | Job di queue `database`, retry 3x backoff | Saat peristiwa |
| Pengingat "belum diambil" (FR-N02) | Perintah terjadwal yang memindai `SIAP_DIAMBIL` sesuai N/M/K lalu mengantre job kirim | Harian 08.00 WIB |
| Pembersihan bisnis demo (FR-M05) | Perintah terjadwal, hapus tenant demo kedaluwarsa beserta seluruh datanya | Harian 03.00 WIB |
| Worker queue | `php artisan queue:work database` di container terpisah | Terus-menerus |

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
| `worker` | `queue:work database` |
| `cron` | `schedule:run` tiap menit |

Reverse proxy publik (mis. Nginx Proxy Manager) berada di luar compose ini dan meneruskan HTTPS ke `web`. Variabel lingkungan penting: `APP_URL`, kredensial DB, `QUEUE_CONNECTION=database`, SMTP global, `APP_TIMEZONE=Asia/Jakarta`. Kredensial WA per bisnis **tidak** di `.env` — tersimpan terenkripsi di database (Laravel encrypted cast).

CI/CD (GitHub Actions): lint (Pint + ESLint) → test (PHPUnit/Pest) → build aset Vite → build & push image → deploy ke VPS (SSH/pull). Migrasi dijalankan saat deploy.

## 13. Strategi Pengujian

1. **Isolasi tenant & cabang** — wajib, otomatis (Bagian 5).
2. **Aturan bisnis** — unit test service: PricingService (kasus di `prd.md` Bagian 16), PaymentService (DP, tolak lebih bayar, turunan status), StampService (matematika penukaran), TransactionStateMachine (transisi ilegal ditolak), RevenueReportService (DP lintas bulan, transaksi batal).
3. **Siklus hidup** — feature test mode tenggang & baca-saja (tulis ditolak 423, publik tetap jalan).
4. **Notifikasi** — test alur keputusan (demo ditekan, batas bulanan → `dilewati_batas`, sekali-kirim).
5. **Demo** — provisioning, isolasi, rate limit, pembersihan.

## 14. Invarian yang Wajib Dijaga (Ringkasan untuk Agentic AI)

1. Tidak ada kueri operasional tanpa scope `business_id`.
2. Catatan `payments` tidak pernah di-update/delete; tidak ada route untuk itu.
3. Transaksi tidak bisa diedit sejak `DIPROSES` — tidak ada endpoint edit untuk status tersebut, bagi peran mana pun.
4. `SUDAH_DIAMBIL` hanya sah bila `status_bayar = LUNAS` (divalidasi di `TransactionStateMachine`).
5. Status hanya maju sesuai peta transisi; `DIBATALKAN` wajib beralasan.
6. Semua harga/promo yang menyentuh transaksi di-snapshot.
7. Notifikasi tidak pernah memblokir alur utama (selalu via queue).
8. Server menolak aksi tulis di mode baca-saja meski UI dimanipulasi.

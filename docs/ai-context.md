# AI Context & Development Mandates — CekLaundry

Baca dokumen ini sebelum merencanakan atau mengubah proyek. Urutan orientasi: [index.md](index.md) → [architecture.md](architecture.md) → [data-model.md](data-model.md) → [user-stories.md](initiate-file/user-stories.md) dan [nfr.md](initiate-file/nfr.md).

**Tahap proyek:** M1–M4 diimplementasikan. Bukti aktual dan batasnya ada di [audit M1](audits/m1-verification.md), [audit M2](audits/m2-verification.md), [audit M3](audits/m3-verification.md), dan [audit M4](audits/m4-verification.md). M5–M6 belum tersedia; jangan menyebut seluruh produk selesai.

## 1. Tech Stack (runtime M1–M4)

| Lapisan | Pilihan dalam rancangan |
|---|---|
| Backend | PHP 8.4, Laravel 12 |
| Database | MySQL 8.4, InnoDB, `utf8mb4` |
| Panel developer/owner/admin | Inertia.js, React dengan TypeScript `strict`, Tailwind CSS, shadcn/ui, Vite; tanpa SSR Inertia |
| Halaman publik | Blade, CSS ringan, JavaScript vanila seperlunya; tanpa bundle React panel |
| Pekerjaan latar | Laravel queue driver `database`; scheduler lewat cron |
| Deployment | Docker Compose di VPS, reverse proxy HTTPS, GitHub Actions |

Versi library yang tidak disebut di [spesifikasi arsitektur](initiate-file/architecture.md) belum ditetapkan. Jangan menebaknya sebagai keputusan proyek.

## 2. Architecture Mandates

1. Rancangan aplikasi adalah monolit Laravel dengan isolasi tenant berdasarkan `business_id`. Kueri operasional dibatasi tenant; admin juga dibatasi cabang. Akses lintas tenant terhadap resource ber-ID menghasilkan 404.
2. Aturan bisnis berada di `app/Services/`; controller tipis, React menangani presentasi. Policy dan validasi server menegakkan otorisasi, mode baca-saja, status transaksi, dan pembayaran.
3. Operasi tulis multi-langkah memakai transaksi database. Catatan pembayaran dan riwayat audit bersifat tambah-saja sesuai [nfr.md](initiate-file/nfr.md).
4. Halaman publik `/t/{kode_resi}` merupakan jalur tanpa tenant login; data pribadi disamarkan. Pengiriman notifikasi melalui queue tidak boleh menggagalkan alur utama.
5. Rincian arsitektur dan invarian mengikuti [spesifikasi arsitektur](initiate-file/architecture.md); skema mengikuti [database-schema.md](initiate-file/database-schema.md). Ringkasan di `docs/` membantu navigasi, bukan menggantikan spesifikasi.

## 3. Rules

**Rule 1 — Status bukti:** Bedakan rancangan, implementasi, dan verifikasi. Klaim "sudah berjalan" hanya boleh berasal dari kode atau hasil uji aktual. `docs/features/` mencatat fitur yang telah diimplementasikan.

**Rule 2 — Keterlacakan:** Setiap ticket implementasi mengacu ke ID `US-…` dari [user-stories.md](initiate-file/user-stories.md) dan ID NFR yang relevan. Sertakan kasus batas, kegagalan, serta isolasi tenant/cabang bila menyentuh data operasional.

**Rule 3 — Sumber kebutuhan:** Kelima spesifikasi tersedia di `docs/initiate-file/`. Hierarki: `prd.md` → `user-stories.md` → `architecture.md` → `database-schema.md` → `nfr.md`. Gunakan matriks traceability di akhir user stories dan [hasil audit final](audits/final-system-audit.md); jangan menghidupkan kembali keputusan lama yang sudah dikoreksi. Audit menyatakan kesiapan spesifikasi, bukan keberhasilan implementasi.

**Rule 4 — Keamanan & lokalisasi:** Jangan simpan kredensial di repo atau log. Gunakan bahasa Indonesia untuk UI/pesan, rupiah bulat, nomor HP `62…`, dan zona waktu Asia/Jakarta sesuai NFR.

## 4. Local Development Environment

Gunakan [development.md](development.md): Docker Compose PHP8.4/MySQL8.4 dan Node22 build stage. `python3 bin/setup-env`, build/up/migrate, lalu bootstrap developer interaktif. Tidak ada password default. Database uji `ceklaundry_test` terpisah dan destruktif; jangan jalankan browser seed bersamaan backend suite. Lihat current-session untuk status verifikasi/CI.

## 5. Documentation Workflow

**Penyimpanan konteks:** Keputusan, status ticket, hasil analisis, dan konteks lintas sesi disimpan dalam file proyek yang dapat di-versioning, bukan memory lokal AI. Spesifikasi awal berada di `docs/initiate-file/`; dokumen workflow dan dokumen baru juga berada di `docs/`.

**Backlog — [backlog.md](backlog.md):** Masukkan ide, bug, dan blocker baru dengan status `READY` atau `BLOCKED`. [user-stories.md](initiate-file/user-stories.md) sudah menjadi inventaris kebutuhan awal; jangan menduplikasinya ke backlog. Hapus item backlog ketika menjadi ticket.

**Planning — [planning/index.md](planning/index.md):** Sebelum menulis kode, buat `docs/planning/TICKET-NNN-nama.md` dari [_template-implementation-plan.md](planning/_template-implementation-plan.md), daftarkan dan urutkan di indeks. Isi keputusan bisnis, kontrak teknis, matriks penerimaan, verifikasi, dan batas lingkup. Status `DRAFT` saat keputusan penting belum pasti; `REVIEW` saat rencana memerlukan keputusan/persetujuan pengguna; `READY` saat pekerjaan dalam lingkup ticket sudah diotorisasi pengguna; `DONE` setelah implementasi dan verifikasi. Permintaan implementasi yang jelas dari pengguna merupakan otorisasi untuk lingkup tersebut; jangan meminta persetujuan berulang. Pertanyaan keputusan yang belum dijawab tetap harus diselesaikan sebelum bagian terkait dieksekusi. Arsipkan ticket `DONE` ke `planning/Ticket-Implemented/` dan hapus dari indeks aktif. Periksa indeks dan arsip untuk nomor berikutnya.

**Feature docs — [features/index.md](features/index.md):** Buat dokumen fitur sesudah fitur diimplementasikan dan diverifikasi. Gunakan bahasa Indonesia; tulis status `Live`, ringkasan, referensi, gotcha yang benar-benar ada, dan tautan terkait. Verifikasi setiap klaim terhadap kode. Gunakan tabel untuk katalog field/method; tanpa emoji dekoratif atau metadata commit di status.

**Decision log — [decision-log.md](decision-log.md):** Catat trade-off bisnis non-obvious, gotcha teknis yang tidak jelas dari kode, atau koreksi asumsi. Jangan ulangi mandat di dokumen ini. Hapus entry yang sudah digantikan setelah rujukannya dipindahkan ke keputusan yang berlaku.

**Sesi berikutnya:** Baca [planning/current-session.md](planning/current-session.md), periksa ticket aktif, lalu perbarui status dan langkah selanjutnya sebelum berhenti.

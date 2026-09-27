# Kebutuhan Non-Fungsional (NFR) — CekLaundry

> **Kedudukan dokumen:** perluasan dari `prd.md` Bagian 12 menjadi kebutuhan terukur ber-ID. Jika ada pertentangan, `prd.md` menang. Setiap NFR wajib dipenuhi; NFR bertanda **[Uji]** wajib punya pengujian otomatis, bertanda **[Ops]** dipenuhi lewat konfigurasi/prosedur operasional.

---

## 1. Kinerja (KIN)

| ID | Kebutuhan |
|---|---|
| KIN-01 | **[Uji]** Publik lengkap P95<3 detik: cold browser, down4Mbps/up1Mbps, RTT150ms, CPU4× throttling, 20 navigasi ke data fixture; ukur setelah QR/status terlihat, tanpa bundle React. |
| KIN-02 | Total berat transfer halaman publik (depan, status, resi) ≤ 200 KB per halaman (HTML+CSS+JS+font, sebelum gambar QR) — halaman publik tidak boleh memuat bundle panel React. |
| KIN-03 | **[Uji]** Dashboard interaktif P95<2,5 detik pada profil jaringan/CPU KIN-01 sesudah login; 20 navigasi, fixture kapasitas DAT-04. |
| KIN-04 | **[Uji]** Tidak ada masalah kueri N+1 pada daftar transaksi, dashboard, dan laporan; semua kueri daftar/laporan memakai indeks yang didefinisikan `database-schema.md`. |
| KIN-05 | Alur "Transaksi Baru" dapat diselesaikan admin dalam < 1 menit untuk pelanggan lama (metrik desain: pencarian pelanggan instan, tambah item tanpa pindah halaman). |

## 2. Keamanan (SEC)

| ID | Kebutuhan |
|---|---|
| SEC-01 | **[Uji]** Password minimal12 karakter dan maksimal72byte UTF-8, bcrypt; initial/reset operator memaksa ganti; perubahan email mencabut sesi/token reset; job auth terenkripsi mengecek token terbaru/user/demo dan hanya satu panggilan; password/credential tidak masuk repo/log/audit/response tersimpan. Remember-me tidak disediakan; tidak ada public user signup. |
| SEC-02 | **[Uji]** Semua jalur resi berbagi30/menit/IP, login5/menit/email+IP serta30/menit/IP, demo3/hari/IP, reset3/jam/email+10/jam/IP. Batas fixed window WIB, check+increment atomik termasuk request paralel; invalid/valid code sama-sama dihitung. IP hanya dipercaya dari proxy terkonfigurasi; 429 ramah. |
| SEC-03 | **[Uji]** wa_token/wa_config/smtp_config encrypted pada TEXT, rahasia tidak dikembalikan bahkan ke form developer (hanya configured indicator/input baru). Worker bergantian dua tenant tidak berbagi SMTP/token melalui singleton. Log/job transaksi/error/audit tidak memuat rahasia atau payload customer; auth job token/recipient terenkripsi, tidak terekspos developer. |
| SEC-04 | **[Uji]** Semua POST panel/publik/demo CSRF; cookies HttpOnly/Secure/SameSite=Lax, HTTPS produksi; SQL terparameterisasi, output escaped. GET status/konfirmasi/print tidak memutasi bisnis. Header no-store/no-referrer/noindex publik teruji. |
| SEC-05 | **[Uji]** Otorisasi berbasis Policy untuk setiap resource; akses lintas peran ditolak (mis. admin membuka route owner → 403). |
| SEC-06 | **[Uji]** DTO publik dan cetak publik masked sesuai PRD 9.1, tidak ada email utuh/ID/actor/tujuan log/histori ledger/link transaksi lain bahkan di HTML/JSON/data attributes; cetak internal identitas utuh perlu policy. Tidak ada full-data render yang hanya disembunyikan CSS. |
| SEC-07 | **[Uji]** Merge, sync, lifecycle, reset, pembatalan memiliki audit atomik pelaku/detail aman; developer tidak membaca audit operasi individual. Reset tidak menyimpan password. Source merge yang dihapus tetap bisa ditelusuri dari snapshot audit owner. |
| SEC-08 | **[Uji]** Matriks PRD 9.3 diuji pada batas −7,0,+7,+8 hari WIB dan is_active=false/demo expiry; must_change_password+BACA_SAJA tidak deadlock. GET status nyata tetap tersedia, email publik/tulis bisnis423, manual wa.me read-only tanpa log; email manual ditolak. Sesi lama user/tenant/branch nonaktif atau admin dipindah langsung kehilangan akses lama. Ganti/reset/logout tetap sesuai matriks dan mencabut sesi/token. |
| SEC-09 | **[Uji]** Endpoint publik FR-C04 **tidak pernah mengubah customers.email**, meski pemegang resi menguasai email baru. Verifikasi24jam versi terbaru: GET baca, POST hanya notification_email; replay/expired/ready/batal/merge source/read-only ditolak. Limits3/jam/resi+10/hari/IP+5/jam/recipient serta shared receipt limiter berlaku request paralel; raw target/pending/signature tidak bocor. Demo tidak menyediakan bypass verifikasi. |

## 3. Isolasi Multi-Tenant (ISO) — paling kritis

| ID | Kebutuhan |
|---|---|
| ISO-01 | **[Uji]** Model dengan kolom `business_id` langsung memakai `BelongsToBusiness`; child tanpa kolom tersebut hanya diakses melalui parent terscope. User bisnis A memperoleh 404/daftar-kosong untuk resource bisnis B (transaksi, pelanggan, laporan, pengaturan, ekspor). |
| ISO-02 | **[Uji]** Admin hanya transaksi/service/child/dashboard/search/notifikasi cabangnya, report/export owner403. Direktori customer+saldo global boleh, ledger/detail transaksi lain tidak. Merge admin hanya bila seluruh transaksi source+target termasuk historis/batal ada di cabangnya; source/target tanpa transaksi boleh. Owner seluruh bisnis, cross-tenant404. |
| ISO-03 | **[Uji]** Developer hanya administrasi+angka jumlah cabang/tx30hari FR-D04, tanpa drill-down/ID operasi. API, audit, failed_jobs, job payload, exception, export tidak menjadi jalan membaca customer/payment/transaksi. Public resi tetap whitelist umum tanpa privilege developer. |
| ISO-04 | Akses lintas tenant merespons **404**, bukan 403, agar keberadaan data tidak bocor. |
| ISO-05 | **[Uji]** Export/search/report/commands/jobs/public/demo memakai konteks tenant yang terverifikasi; context worker dihapus pada finally. Dua job tenant berbeda berurutan tidak berbagi cache/SMTP/data; konteks kosong fail closed. |
| ISO-06 | **[Uji]** `services.business_id = branches.business_id`; `transactions.business_id = branches.business_id = customers.business_id`; `payments`, `status_histories`, `notification_logs`, `loyalty_histories` cocok dengan tenant parent. FK komposit dan service menolak ID lintas tenant; `users.branch_id`, pivot promo, dan hadiah loyalti juga divalidasi tenant/cabang. Tenant tidak diambil dari URL/input pengguna. |

## 4. Keandalan & Integritas (AND)

| ID | Kebutuhan |
|---|---|
| AND-01 | **[Uji]** Fault injection setiap write pada create/item/payment/ledger/status/batal/merge/sync menunjukkan rollback seluruh unit. Semua mutasi domain memakai business lock dahulu; customer/transaction/log berurut ID, satu tenant/unit, tanpa network call dalam lock. |
| AND-02 | **[Uji]** Invarian: kumulatif `payments` ≤ `total_akhir`; untuk total Rp0 status `LUNAS` tanpa payment Rp0, selebihnya `BELUM_BAYAR`/`DP`/`LUNAS` sesuai jumlah bayar; `customers.stamp_count = SUM(loyalty_histories.jumlah)` termasuk kompensasi pembatalan dan merge. |
| AND-03 | **[Uji]** Hanya definitely_rejected boleh retry60/300detik maksimal3 panggilan/log; claim mati sebelum marker tidak memakan attempt. Unknown/crash setelah marker tidak dikirim ulang otomatis; terlihat perlu_pemeriksaan. Retry job/queue:retry terhadap terminal no-op. Gagal final memiliki log dan failed job sanitized, tanpa mengubah status laundry. |
| AND-04 | **[Uji]** UNIQUE notification_key non-null untuk ready/reminder/verifikasi/manual; duplicate scheduler/job/request/key tidak menggandakan send. Manual UUID sama replay, UUID baru aksi sadar. Email/WA independen; retention/purge tidak membuka identity untuk transaksi nyata. Unknown terminal konservatif berlaku seluruh adapter, tanpa asumsi vendor idempotency. |
| AND-05 | **[Ops]** Backup terenkripsi harian≥7hari lokasi terpisah+APP_KEY aman; restore drill RPO≤24jam/RTO≤4jam untuk pemulihan database. Database consistency tidak menjamin external side-effect deduplication: successful SMTP/WA setelah titik backup yang hilang dalam window RPO tidak diketahui DB hasil restore. Sebelum instance pulih memakai provider, OUTBOUND_RESTORE_HOLD=true di konfigurasi luar backup DB, worker/instance lama dihentikan. Rekonsiliasi operator, review log nonterminal/token/pekerjaan outbound lama, cutoff outbound_resume_at dan cutover terkoordinasi mengikuti PRD9.2.1/arsitektur9. Tidak ada klaim exactly-once melintasi restore; outbound baru aktif setelah rekonsiliasi, tanpa replay otomatis window hilang. |
| AND-06 | **[Uji]** Dua pembayaran paralel pada sisa sama memakai lock transaksi: hanya pembayaran yang muat pada sisa berhasil, tak ada overpay, status bayar tetap benar; edit harga `DITERIMA` bersamaan dengan pembayaran juga tidak merusak total. |
| AND-07 | **[Uji]** Dua penukaran paralel memakai lock customer: saldo dicek setelah lock; hanya penukaran yang cukup saldo berhasil dan penukaran tidak membuat saldo negatif. Pembatalan mengembalikan jumlah stempel aktual tanpa memakai N terkini. |
| AND-08 | **[Uji]** Item nama/satuan/harga/minimum/durasi/subtotal, promo nama/tipe/nilai/potongan, hadiah max kg/delta N serta email transaksi/tujuan log email tidak berubah saat master/customer/config berubah. Pengecualian WA belum pernah attempt hanya AND-28; bukan retarget email. Edit finansial sah memakai quote terbaru dan mengganti snapshot atomik; quote stale409; edit nonfinansial tidak reprice. |
| AND-09 | **[Uji]** Harga hanya DITERIMA belum LUNAS tanpa payment/ledger. Total0 terkunci bahkan loyalti mati, total0 aktif+1 sekali, total0 redemption tanpa+1. Catatan/estimasi/perkiraan baju hanya DITERIMA; identity tx immutable kecuali merge. FR-C04 hanya email transaksi setelah verifikasi. Expected_version mencegah tab stale menimpa data. |
| AND-10 | **[Uji]** Real MySQL connection: log+jobs+state satu commit, fault sebelum/sesudah insert job/commit tidak menyisakan dual-write gap. Lease300detik/token CAS, timeout120/retry_after180; kill sebelum marker dapat reclaim tanpa kirim worker lama, kill sesudah marker/provider accepted sebelum finalize→unknown tanpa resend. Pending due hilang jobs/failed direkonstruksi scanner≤6menit pada scheduler sehat; duplicate queue tetap aman. |
| AND-11 | **[Uji]** WA99/100 dan dua reservasi paralel memberi satu slot. Tertunda/diproses/berhasil/perlu_pemeriksaan occupied. Key retry satu slot; failure definite final melepas, unknown mempertahankan. Sebelum attempt0 pindah bulan re-reserve; success lintas tengah malam tetap bucket awal, retry lintas bulan setelah attempt>0 dihentikan. Limit0/null/lowering di bawah occupied diuji; UI sukses berdasarkan bucket, bukan sent_at. |
| AND-12 | **[Uji]** Demo log per kanal dengan tipe/tujuan/key/status ditekan_demo; seluruh transport termasuk password-reset/verifikasi dan link wa.me/telpon tidak keluar. Demo2cabang role switch hanya admin reserved Cabang Utama; arbitrary user/tenant IDs ditolak. Audit pembatalan atomik. |

### 4.1 Skenario integrasi adversarial wajib

Semua uji concurrency berikut memakai minimal dua koneksi/proses **MySQL 8.4 nyata**, barrier untuk memaksa interleaving, tanpa enclosing test transaction tunggal/SQLite. Ekspektasi merupakan kontrak tes implementasi, bukan hasil tes runtime saat audit spesifikasi.

| ID | Kebutuhan |
|---|---|
| AND-13 | **[Uji]** Payment vs cancel: payment lebih dulu boleh tercatat lalu excluded, cancel lebih dulu payment ditolak. Cancel paralel dua kali menghasilkan satu audit/compensation; payment vs financial edit tetap SUM≤total. |
| AND-14 | **[Uji]** Enumerasi 5×5 transisi: tepat tiga forward edges, tiga cancel edges, retry state sama no-op; lainnya ditolak. SUDAH_DIAMBIL perlu LUNAS, payment terminal ditolak, history/waktu/alasan konsisten, tidak ada earning saat status laundry maju. |
| AND-15 | **[Uji]** Merge source/target dengan aktif/historis/dua saldo termasuk negatif, email/HP berbeda; target identity tetap, unique phone terjaga, ledger ownership=transaction customer. Race merge vs create/payment/redemption/cancel serta A→B vs B→A serial tanpa orphan/double delta/deadlock permanen. WA mengikuti AND-28 sebelum source dihapus terakhir; nomor source boleh dipakai ulang setelah commit tanpa histori lama/otorisasi kiriman baru dari log lama; pending verifikasi source gugur dan email snapshot tetap. Fault pada penyesuaian log rollback seluruh merge/pelepasan nomor. |
| AND-16 | **[Uji]** Loyalti active→inactive→active/N10→N5: tidak retroaktif, compensation tetap delta asal; earning sudah dipakai lalu dibatalkan menghasilkan negatif, tampilan bertanda, earning berikutnya mengurangi utang, redemption baru tidak dapat membuat negatif. |
| AND-17 | **[Uji]** Untuk total>0 dan state/lifecycle sah, total_paid=SUM payment di bawah business root lock: partial pertama ketika total_paid=0 hanya jika business_settings.dp_enabled=true saat payment; nominal=sisa selalu boleh; 0<total_paid<total mengizinkan cicilan/pelunasan saat saklar false. Create saat on lalu off sebelum partial pertama→tolak; create saat off lalu on→boleh partial; DP30000 lalu off→boleh cicilan/pelunasan. Toggle-off commit dahulu→partial pertama ditolak; partial commit dahulu→DP berjalan boleh dilanjutkan. Payment awal create memakai aturan sama, replay key/hash berhasil tetap hasil lama. Total0 LUNAS tanpa payment0; jumlah0/negatif/overpay/overflow/terminal ditolak; tidak ada snapshot izin DP. |
| AND-18 | **[Uji]** Respons create/payment hilang sesudah commit lalu replay UUID+hash sama menghasilkan resource sama; hash beda409. Replay setelah admin berpindah cabang tidak membuka resource lama; dua tab edit expected_version lama409. |
| AND-19 | **[Uji]** Branch deactivate vs create, admin reassignment/nonaktif vs payment, master sync/config vs quote save menghasilkan urutan valid setelah root lock. Quote berubah409; tidak ada cabang nonaktif dengan transaksi aktif. Normalisasi nama/case/space dan sync preview reactivation deterministik. |
| AND-20 | **[Uji]** Scheduler overlap/outage/settings berubah: firstN dari ready, nextM dari last reservation, satu nomor/putaran, K membatasi reservations, no channels tidak consume, WA skipped consume, manual tidak mengubah cursor. N/M/K tetap≥1 saat switch mati. |
| AND-21 | **[Uji]** Job tertunda lalu pickup/cancel/read-only/switch off/email version usang→dilewati_kondisi; tidak replay saat reaktivasi, termasuk off→on cepat/expired→perpanjang sebelum worker berjalan. Preflight sah lalu state berubah diperbolehkan in-flight, tidak menjadwalkan ulang. Stale worker gagal CAS; accepted terlambat hanya finalisasi token sama yang masih sah. Recipient berubah/restore mencabut token sehingga accepted terlambat tidak menimpa review (AND-28/27). |
| AND-22 | **[Uji]** Pricing kg/item/minimum/stamp/promo: actual2kg,min3kg,@7000,rewardmax3 →subtotal21000,stamp14000; promo10%→700,total6300. Nominal100000 atas base7000→total0. Persen/minimum/date/cabang tak sah ditolak, half-up0,5 dan overflow serta qty/decimal bounds teruji. |
| AND-23 | **[Uji]** Ledger CHECK/sign/unique dan service menolak compensation tanpa asal/dua jenis earning+redemption; SUM histories/cache selalu sama termasuk merge. Injeksi transaksi FK lintas tenant, invalid actor/branch/service/promo ditolak. Snapshot/SET NULL tidak mengubah nominal lama. |
| AND-24 | **[Uji]** Laporan July DP/August pelunasan, cancellation September mengeluarkan keduanya retrospektif; WIB boundary, tujuh hari inklusif, kg aktual bukan minimum, banyak payment tak menggandakan item, late hanya DITERIMA/DIPROSES. CSV/grafik/dashboard sesuai definisi PRD 7.10 dan snapshot baca yang sama; formula injection dinetralkan. |
| AND-25 | **[Uji]** Email manual double-click/replay tidak menggandakan; cooldown/limit atomic; key baru setelah unknown perlu konfirmasi; wa.me manual tidak masuk API quota. Demo password-reset/telepon/verifikasi juga tidak melakukan network send. |
| AND-26 | **[Uji]** PWA/cache: login tenantA→logout→tenantB/back/offline tidak menampilkan dataA; hanya hashed static assets cache, semua dynamic no-store/network-only. Deploy memperbarui aset tanpa menghilangkan form belum disimpan; offline tidak mereplay write. |
| AND-27 | **[Uji]** Backup sebelum successful email maupun WA, lalu restore backup tanpa log send tersebut: sistem tidak mengklaim mengetahui send yang hilang dalam window RPO; OUTBOUND_RESTORE_HOLD=true memblokir seluruh provider SMTP/WA call walau worker/recovery/scheduler hidup sebelum rekonsiliasi, termasuk manual/verifikasi/auth. Hold tidak menambah attempt/slot/cursor; business operation aman tetap commit tanpa backlog kiriman. Review log nonterminal asal backup, cabut token termasuk unknown, pertahankan slot existing, buang job outbound/auth lama dan invalidasi token reset/pending verifikasi lama. Sesudah rekonsiliasi dan cutoff outbound_resume_at: created_at log serta waktu_siap_diambil ready/reminder harus >cutoff; tidak ada replay/catch-up historis/hold, waktu sama diblokir, cutoff bertahan saat restart. Request manual/verifikasi/auth baru sesudah cutoff boleh sesuai aturan. Ini bukan jaminan deduplikasi external side effect yang hilang. wa_config WABA/Wablas mandatory fields/template5parameter, HTTP200+body error bukan accepted, SMTP disconnect setelah DATA unknown, provider berubah sesudah attempt tanpa fallback tetap diuji. |
| AND-28 | **[Uji]** WA ready/reminder pending, delivery_started_at null DAN attempt_count=0, edit nomor A→B atau merge source→target B: tujuan menjadi B dengan notification_key/nomor/slot sama; claim lama dicabut, worker membaca ulang setelah root→customer→transaction→log locks. Nomor A dipakai customer lain sesudah commit tidak mengotorisasi send baru ke A. Marker ada ATAU attempt_count>0 (termasuk retry marker-null) + nomor berubah→perlu_pemeriksaan dengan recipient_berubah, snapshot/attempt/bucket tetap, token dicabut, tanpa retry/retarget; accepted terlambat dan A→B→A tidak revive log. Marker lebih dahulu berarti in-flight tak dapat ditarik, bukan jaminan membatalkan call yang sudah diotorisasi. Edit lebih dahulu membuat worker stale gagal CAS. Terminal historis tidak berubah; email transaksi/log tetap snapshot. Fault rollback atomik; internal maintenance seluruh cabang customer tidak membocorkan data/menambah hak admin; merge tetap policy cabang. |

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
| OBS-01 | **[Ops]** Log terstruktur sanitized; developer hanya metadata tenant/job/time/code, tidak payload operasi. Alert cron heartbeat>5menit, pending due tertua>5menit, DB/backup gagal; failed_jobs terpantau tanpa credential/customer. |
| OBS-02 | Semua upaya dan terminal skipped/unknown tampak pada detail transaksi terscope; UI tidak menyatakan dibuka_manual/unknown sebagai berhasil. Penghitung WA sukses memakai wa_quota_month, pending/unknown terpisah; sent_at hanya bukti waktu accepted dicatat. |
| OBS-03 | Kesalahan server memberi halaman error ramah kepada pengguna; detail teknis hanya masuk log. |

## 10. Data, Retensi & Kapasitas (DAT)

| ID | Kebutuhan |
|---|---|
| DAT-01 | **[Uji]** Payment/status/ledger/audit append-only selama tenant ada, kecuali merge hanya FK customer ledger dan demo purge. Semua FK ON DELETE sesuai schema; master/user/branch tidak menghilangkan sejarah, optional service/promo SET NULL menjaga snapshot. Tidak ada endpoint hard-delete tenant normal. |
| DAT-02 | **[Uji] [Ops]** Demo expiry tepat7×24jam menolak akses/sesi/status; cron tiap menit, sistem sehat purge≤5menit sesudah expiry. Simulasi scheduler mati lalu pulih membersihkan pada putaran pertama, dua purge paralel aman. Domain/queue/session/reset/cache demo bersih tanpa menonaktifkan FK checks; backup mengikuti retensi7hari, bukan data live. |
| DAT-03 | **[Uji]** Seluruh notification_logs beserta notification_key dipertahankan sepanjang umur transaksi nyata, bukan dipangkas setelah12bulan; demo purge satu-satunya pengecualian. Identity tidak boleh hilang lalu terkirim ulang. |
| DAT-04 | Asumsi kapasitas desain (bukan batas keras): hingga ±200 bisnis aktif, ±50.000 transaksi/bulan agregat, berjalan nyaman pada satu VPS (2–4 vCPU, 4–8 GB RAM). Desain kueri & indeks mengacu pada angka ini. |
| DAT-05 | Tidak ada data pelanggan yang dipakai lintas bisnis; tidak ada pelacakan/analitik pihak ketiga di halaman publik. |

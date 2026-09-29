# Verifikasi M3 — Notifikasi

Status: implementasi M3 dan verifikasi lokal selesai pada 29 September 2026. Bukti di bawah membedakan hasil MySQL/Chrome, kontrak adapter dengan HTTP fake, dan pengiriman penyedia sungguhan. Tidak ada klaim email/WA telah sampai ke pelanggan nyata atau bahwa restore dapat mengetahui efek eksternal yang hilang dari backup.

## Lingkungan dan bukti

- MySQL 8.4 QA `ceklaundry_test`, terpisah dari database development: `php artisan test --fail-on-warning` lulus **105 tes/846 assertion** pada image akhir perjalanan browser. Uji merge WA dan isolasi worker dua bisnis kemudian lulus terarah (masing-masing 6 dan 4 assertion). Termasuk proses MySQL terpisah untuk worker, verifikasi email versus ready, scheduler, kuota, penerima WA, dan manual email.
- [CI PR pada commit `12a2762`](https://github.com/cleveradit/ceklaundry/actions/runs/36575415416) lulus seluruh gate: **106 tes/852 assertion** termasuk merge WA, Pint, frontend, dokumentasi, browser M1–M3 pada Chromium 153. Uji isolasi worker dua bisnis adalah tambahan setelah run ini dan menunggu gate commit handoff.
- PHP Pint 181 file lulus. ESLint, TypeScript, Vite build dan quality-gate probe lulus. Validator spesifikasi final dan `git diff --check` lulus.
- Browser Chrome 154 pada QA terpisah: perjalanan M1, M2 dan M3 lulus berurutan pada image final. M3 mencakup pengaturan owner, form email publik tanpa JavaScript, reservasi ready, dan email manual. Artefak tangkapan layar di `test-results/` diabaikan Git.
- `WhatsAppProviderTest` menguji respons HTTP fake Fonnte, Wablas, dan WABA, termasuk 200 dengan body error, 5xx ambigu, header, endpoint, dan lima parameter template. Kredensial provider nyata tidak tersedia; penerimaan provider sungguhan belum diuji.
- `RestoreHoldTest` dan `NotificationFlowTest` membuktikan guard, rollback log+job, command rekonsiliasi, cutoff detik yang sama, dan ready historis. Kedua command `app:reconcile-notification-restore` dan `app:reconcile-auth-restore` juga lulus dry run pada QA dengan hold aktif setelah browser journey. Dry run restore fisik dari backup, distribusi konfigurasi lintas instance, dan restart produksi masih tugas staging.

Kode bukti: **L** = pengujian lokal; **K** = kontrak/mock tanpa provider nyata; **P** = implementasi diperiksa dengan bukti parsial; **M6** = integrasi demo menunggu M6.

## Matriks penerimaan

| AC | Bukti | Status |
|---|---|---|
| US-301.1 | `NotificationFlowTest`, tampilan `emails/ready.blade.php`; accepted disimulasikan | K |
| US-301.2 | Duplikasi job tidak mengulang log berhasil; kanal terpisah | L |
| US-301.3 | Regresi M2 ready tanpa email | L |
| US-301.4 | Invalidation status/lifecycle dan preflight worker | P |
| US-301.5 | Hold dan cutoff diuji; window RPO nyata tidak dapat dibuktikan dari DB restore | P |
| US-302.1 | Jadwal N=2 dan cursor pada MySQL | L |
| US-302.2 | Uji N/M/K H2/H4/H6/H8 | L |
| US-302.3 | Scheduler hanya memilih `SIAP_DIAMBIL` | P |
| US-302.4 | Pengaturan owner dan recheck pada scheduler | L |
| US-302.5 | Key per kanal/nomor pada dispatcher; reservasi email/WA independen | P |
| US-302.6 | Uji dua scheduler dan tidak burst setelah due | L |
| US-302.7 | Uji tanpa kanal tidak mengubah cursor; kuota WA penuh tetap log skipped | L |
| US-302.8 | Uji off→on dan batas K tidak mereset cursor | L |
| US-302.9 | Pending lama terminal saat off; lifecycle memakai invalidator | L |
| US-302.10 | Cutoff waktu siap historis tanpa cursor/log baru | L |
| US-303.1 | Pending/version/expiry dan log+job satu transaksi | L |
| US-303.2 | Batas per resi diuji; limit IP/recipient memakai cache lock atomik, race IP belum diukur | P |
| US-303.3 | GET signed tanpa mutasi, POST hanya email transaksi; master/tx lain tetap | L |
| US-303.4 | Link stale ditolak; preflight cek versi/status/expiry | L |
| US-303.5 | Form disembunyikan pada read-only, POST 423, GET tanpa mutasi | L |
| US-303.6 | Ready/merge menggugurkan pending sebelum commit; ready memakai snapshot aktif | P |
| US-303.7 | Email transaksi merupakan snapshot terpisah dari master | L |
| US-303.8 | Dua proses MySQL: ready menang atas konfirmasi, link ditolak | L |
| US-304.1 | Browser email manual, service link WA pengingat | L |
| US-304.2 | WA manual hanya `dibuka_manual`, slot API null | L |
| US-304.3 | Replay/cooldown dan dua proses UUID sama; warning unknown | L |
| US-304.4 | State/read-only dijaga; preview demo menunggu M6 | M6 |
| US-305.1 | Cast terenkripsi dan uji respons developer tanpa credential | L |
| US-305.2 | WA default off, email serta `wa.me` manual tetap | L |
| US-305.3 | Context worker dibersihkan, transport dibangun per job; isolasi dua bisnis diuji terarah, pergantian dua SMTP nyata belum diuji | P |
| US-305.4 | Validasi WABA/Wablas, lima parameter template; HTTP fake | K |
| US-306.1 | Browser halaman owner; hitung berhasil dan slot pending/review terpisah | L |
| US-306.2 | Validasi N/M/K dan penurunan limit di bawah occupied | L |
| US-307.1 | WA accepted pada adapter dengan HTTP fake; tidak ada kiriman nyata | K |
| US-307.2 | Saklar per peristiwa diperiksa saat reservasi/preflight | P |
| US-307.3 | Race kuota limit1: satu slot, satu skipped, kedua email tetap | L |
| US-307.4 | Bucket bulan WIB lama tidak masuk penghitung baru | L |
| US-307.5 | Dua proses MySQL untuk slot terakhir | L |
| US-307.6 | Pending bulan lama yang penuh dilewati sebelum attempt | L |
| US-307.7 | Retry attempted bulan baru dilewati oleh preflight; belum diuji sebagai kasus terpisah | P |
| US-307.8 | Body error/5xx pada HTTP fake; SMTP unknown disimulasikan | K |
| US-307.9 | Edit nomor sebelum marker memakai nomor baru; callback accepted lama gagal CAS | L |
| US-307.10 | Hold/cutoff menolak WA; efek setelah backup tetap tidak diketahui | P |
| US-308.1 | Browser detail menampilkan log per kanal | L |
| US-308.2 | Tiga definite rejection dengan backoff lalu gagal, status laundry tetap | L |
| US-308.3 | Key kanal terpisah; kuota race membuktikan email tetap tertunda | L |
| US-308.4 | Unknown terminal dan replay tidak memanggil transport kedua | L |
| US-308.5 | Log WA manual resi/pengingat tidak dihitung sebagai sukses API | L |
| US-308.6 | Fault sebelum commit membatalkan log dan job bersama | L |
| US-308.7 | Marker expired menjadi review; reclaim sebelum marker mengikuti scanner, belum fault proses mati fisik | P |
| US-308.8 | Scanner due/lease ada; hilangnya job dan callback terlambat diuji sebagian | P |
| US-308.9 | Dua worker satu call, job terminal no-op, token recipient dicabut | L |
| US-308.10 | Hold dan batas RPO terdokumentasi; backup nyata belum direstore | P |
| US-308.11 | Hold tanpa log/cursor baru, rekonsiliasi mencabut pending/job; auth guard diuji M1 | L |
| US-308.12 | Command rekonsiliasi dan cutoff detik sama teruji; cutover lintas instance menunggu staging | P |
| US-308.13 | Guard cutoff berada di reserve/worker/transport; restart deployment nyata menunggu staging | P |

## Regresi dan handoff M2

| Lingkup dari audit M2 | Bukti M3 | Status |
|---|---|---|
| US-202 AC4–5: email/verify memakai limiter publik, payload dan header aman | `ReceiptLimiterTest`, `NotificationFlowTest`, Chrome no-JS M3 | L |
| US-208 AC1–5: email transaksi tidak merusak edit/status/keuangan | `M2JourneyTest`, `TransactionLifecycleTest`, `ReceiptEmailVerificationConcurrencyTest` | L |
| US-210 AC2: pembukaan WA manual tercatat | `NotificationFlowTest` (`dibuka_manual`); demo click menunggu M6 | L/M6 |
| US-213 AC5–9: perubahan nomor saat WA pending/claimed/attempted | `NotificationFlowTest`, `WaRecipientConcurrencyTest` | L |
| US-214 AC6–9: merge dan penerima WA | `NotificationFlowTest` menguji retarget pending dan token attempted dicabut pada merge; race dua proses merge dan worker diwakili tes root lock masing-masing | L |
| US-215 AC3: batas tenant untuk notifikasi | `TenantIsolationTest`, `NotificationFlowTest`, controller role/scoping; promo/loyalti menunggu M4 | L/M4 |

Hasil CI remote di atas menguji commit `12a2762`; gate pada commit handoff akhir akan dicatat setelah selesai.

## Handoff

Integrasi demo suppression dan preview tetap berada di M6. Pengiriman nyata memerlukan credential tenant dan alamat tujuan uji yang dikendalikan. Sebelum produksi, jalankan latihan restore backup beserta fence proses lama, konfigurasi hold di luar database, rekonsiliasi, cutover cutoff seragam, dan uji ulang lintas restart. Jangan memakai ketiadaan log hasil restore sebagai bukti pesan belum dikirim.

# Pengembangan lokal CekLaundry

## Lingkungan terverifikasi

28 September 2026: WSL Ubuntu, checkout `/home/hp/projects/ceklaundry`, Docker Engine29.8.0/Compose5.5.1, PHP8.4 dan MySQL8.4 dalam container, Node22 untuk build. PHP host8.5.4 dan ketiadaan Node host tidak menghalangi setup. Disk awal sekitar932GB tersedia. Port8088 tersedia ketika dipilih; database tidak dipublikasikan. Proyek/container lain tidak dihentikan.

Perintah berikut dijalankan di root repo pada WSL. Dalam Codex Windows, awali dengan `rtk proxy wsl -d Ubuntu --cd /home/hp/projects/ceklaundry --`; RTK hanya terpasang di host Windows. Bila RTK tersedia langsung di Linux, awali perintah dengan `rtk proxy`.

## Setup bersih

```bash
python3 bin/setup-env
docker compose config --quiet
docker compose build
docker compose up -d --wait
docker compose exec -T app php artisan migrate --force
docker compose exec app php artisan app:bootstrap-developer
```

Buka `http://localhost:8088/login`. Command terakhir meminta nama, email dan password tersembunyi (minimal12 karakter, maksimal72 byte UTF-8); tidak ada akun/password bawaan. Developer wajib mengganti password saat masuk, lalu dapat membuat bisnis/owner. Owner membuat cabang, admin, master, dan menyalin master lewat pratinjau. Database development tidak diisi fixture QA.

`bin/setup-env` menghasilkan APP_KEY dan dua password database acak, izin0600, tidak mencetak rahasia, dan menolak menimpa `.env` existing. Bila port sudah digunakan, ubah `APP_PORT` serta `APP_URL` pada `.env`; jangan hentikan layanan lain. `.env.example` tidak mengandung rahasia. Jangan mencetak `docker compose config` tanpa `--quiet`.

Seeder alternatif `DatabaseSeeder`/`DeveloperSeeder` memerlukan environment `CEKLAUNDRY_BOOTSTRAP_NAME`, `CEKLAUNDRY_BOOTSTRAP_EMAIL`, `CEKLAUNDRY_BOOTSTRAP_PASSWORD` dari secret manager/job environment ke proses `php artisan db:seed --force`. Jangan letakkan nilainya pada argumen command/history/repository. Email existing ditolak, bukan direset diam-diam.

## Runtime dan perubahan kode

| Layanan | Fungsi |
|---|---|
| app | PHP-FPM, Laravel; bukan server SSR |
| web | Nginx, port lokal8088; access log dimatikan agar URL reset tidak terekam |
| db | MySQL8.4, InnoDB, utf8mb4_0900_as_ci, +07:00, READ COMMITTED |
| worker | Database queue, retry_after180/durasi kerja120; job auth mempunyai tries1 |
| cron | `schedule:run` tiap menit, scanner lifecycle dan heartbeat cache |

Environment diinjeksi oleh Compose. Entrypoint membuat placeholder `.env` kosong bila tidak ada; tidak menyalin rahasia ke image. Volume storage memakai nocopy dan direktori dibuat saat runtime untuk menghindari perebutan inisialisasi tiga container.

Kode disalin ke image. Setelah mengubah kode, jalankan `docker compose build` lalu `docker compose up -d --wait`; perubahan migrasi diterapkan melalui command migrate. `docker compose down` menghentikan runtime tanpa menghapus volume. Jangan gunakan `down -v` pada data development pengguna.

Konfigurasi SMTP global ada di `.env` (`MAIL_*`). Default localhost1025 adalah placeholder lokal, bukan provider aktif. Uji memakai transport array/mock; pengiriman nyata perlu konfigurasi SMTP operator. Jangan memakai mailer log untuk reset karena body berisi token. Tidak ada pengiriman email ke pihak nyata dalam verifikasi ini.

Produksi kelak wajib `APP_ENV=production`, `APP_DEBUG=false`, HTTPS/reverse proxy tepercaya eksplisit melalui `TRUSTED_PROXIES`, APP_URL HTTPS. Cookie produksi dipaksa Secure; semua sesi HttpOnly/SameSite=Lax. Hardening/deploy/restore drill produksi belum diklaim selesai pada M1.

## Kualitas dan database uji

```bash
docker compose exec -T app vendor/bin/pint --test
docker compose exec -T app php artisan test --fail-on-warning
docker build --target frontend -t ceklaundry-frontend -f docker/php/Dockerfile .
docker run --rm ceklaundry-frontend npm run lint
docker run --rm ceklaundry-frontend npm run typecheck
docker run --rm ceklaundry-frontend npm run build
python3 docs/audits/validate-final-specs.py
git diff --check
```

Init database membuat `ceklaundry` dan `ceklaundry_test`. Suite menolak database selain `ceklaundry_test`, memakai MySQL asli, dan tidak boleh dijalankan paralel dengan browser seed. Tes concurrency menggunakan proses PHP terpisah, barrier file, root lock nyata, tanpa enclosing test transaction. Test lainnya memakai RefreshDatabase. Tes dapat menghapus seluruh isi database uji; tidak memerlukan penghapusan volume development.

Pada volume lama yang belum menjalankan init script, buat database uji dan grant user aplikasi secara administratif sebelum tes. Jangan mengganti DB_DATABASE test menjadi database pengguna agar tes bisa berjalan.

## Browser QA

Setelah backend suite selesai, image terbaru harus tersedia. Jalankan:

```bash
docker compose -f compose.yaml -f compose.qa.yaml up -d qa
docker compose -f compose.yaml -f compose.qa.yaml exec -T qa php tests/Support/browser-seed.php
mkdir -p test-results
docker compose -f compose.yaml -f compose.qa.yaml cp qa:/app/storage/app/private/browser-fixture.json test-results/browser-fixture.json
npm ci
npx playwright install --with-deps chromium
npm run test:browser
docker compose -f compose.yaml -f compose.qa.yaml stop qa
```

Node22 lokal diperlukan hanya untuk browser runner ini; CI memasangnya otomatis. Alternatif Windows memakai Node bundel dan Chrome terpasang, `CHROME_PATH` menunjuk executable dan `BROWSER_FIXTURE` menunjuk file fixture. URL default QA `http://127.0.0.1:8089`; bisa diganti lewat BROWSER_URL. Jangan jalankan QA pada DB development. Fixture menghasilkan kredensial sementara hanya dalam file privat/ignored; screenshot di `test-results/` tidak berisi password. CI hanya mengunggah PNG, tidak file credential. Hapus file fixture privat setelah QA. Ulangi migrasi kosong database uji melalui container QA bila diperlukan, bukan volume development.

## Pemulihan outbound autentikasi

Sebelum memulihkan data: hentikan producer/web dan worker, aktifkan `OUTBOUND_RESTORE_HOLD=true` pada semua instance dan muat ulang konfigurasi. Selama hold, request reset tetap generik dan tidak membuat token/job; job lama tidak mengirim. Dengan hold aktif, jalankan `php artisan app:reconcile-auth-restore`: token serta job/failed-job auth dibuang atomik, job jenis lain dipertahankan.

Tetapkan `OUTBOUND_RESUME_AT` waktu WIB format `YYYY-MM-DD HH:MM:SS` yang sama di semua instance. Setelah rekonsiliasi, buka hold dan jalankan ulang instance. Hanya request token setelah cutoff yang boleh mengirim; waktu sama/sebelumnya atau konfigurasi cutoff tidak valid ditolak. Pengguna meminta tautan baru. Jangan retry manual job auth gagal/ambigu: hanya satu percobaan SMTP diizinkan. Rekonsiliasi transport operasional penuh menjadi pekerjaan M3/M6.

## Probe runtime non-destruktif

Pada runtime lokal/CI sesudah migrasi, `docker compose exec -T app php tests/Support/runtime-check.php dispatch` mengantrekan probe tanpa email/data bisnis. Setelah worker memproses dan cron berdetak (maksimal satu menit), jalankan command yang sama dengan `verify`. Hasil PASS membuktikan worker database dan heartbeat cron. Probe ditolak pada APP_ENV=production.

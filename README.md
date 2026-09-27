# CekLaundry

Aplikasi laundry multi-bisnis. Tahap persiapan dan fondasi M1 tersedia: autentikasi tiga peran, bisnis/masa aktif, cabang/admin, katalog, dan sinkronisasi layanan atomik.

Runtime: PHP 8.4, Laravel 12, MySQL 8.4, Inertia/React/TypeScript, Tailwind, Docker Compose. Halaman publik memakai Blade; transaksi dan cek resi merupakan pekerjaan M2 berikutnya.

Mulai dari [panduan pengembangan](docs/development.md), [fitur M1](docs/features/m1-fondasi-dan-tenant.md), dan [bukti verifikasi](docs/audits/m1-verification.md). Tidak ada password developer bawaan; bootstrap dilakukan dengan prompt tersembunyi.

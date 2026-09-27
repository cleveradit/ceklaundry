# Final System Audit — CekLaundry

Tanggal audit: 27 September 2026. Status repository: pra-implementasi. Audit ini memverifikasi kontrak spesifikasi, bukan membuktikan aplikasi telah berjalan.

## Hasil

**READY_FOR_IMPLEMENTATION** untuk lima spesifikasi sebagai satu paket, setelah focused final fixes recipient WA, restore hold/RPO, dan DP toggle di bawah. Tidak ada defect material atau keputusan produk terbuka yang tersisa pada area perubahan. Audit sistem sebelumnya tetap menjadi dasar area stabil; sesi perbaikan ini hanya mengaudit ulang dependency yang berubah, tidak merancang ulang stack/milestone/ledger/state machine.

Implementasi masih wajib membuktikan AC/NFR melalui MySQL 8.4, proses paralel, fault injection, adapter contract tests, dan browser/PWA/print tests. Keberhasilan pemeriksaan Markdown tidak dianggap keberhasilan runtime.

## File dan sumber kebenaran

1. [PRD](../initiate-file/prd.md): perilaku produk, state/action matrix, lifecycle, finansial/loyalti, merge, kanal, demo, definisi laporan.
2. [User stories](../initiate-file/user-stories.md): 51 stories, 257 AC, matriks eksplisit 72 FR→AC→DB→service→NFR.
3. [Arsitektur](../initiate-file/architecture.md): protokol lock, idempotensi request, queue/lease/recovery, kuota, transport, otorisasi, purge.
4. [Database](../initiate-file/database-schema.md): fields/constraint/index/FK/snapshot, cache dan nilai derived, kepemilikan tenant setiap tabel.
5. [NFR](../initiate-file/nfr.md): 73 NFR; 49 bertanda [Uji], termasuk 29 kelompok invariant kritis yang diperiksa validator.

Orientasi repository (`ai-context`, index, backlog, current-session) juga dikoreksi karena sebelumnya masih menyatakan PRD tidak tersedia. Tidak ada kode aplikasi atau milestone produk yang ditambahkan/dipindah.

## Defect dan keputusan perbaikan

| Severity | Temuan | Keputusan final dan rujukan |
|---|---|---|
| Critical | Pemegang resi dapat memverifikasi email miliknya lalu mengganti email master customer tanpa membuktikan identitas customer | FR-C04 hanya mengganti email transaksi; master admin/owner; SEC-09 |
| Critical | Kontrak gabungan edit/payment/cancel/redemption/merge tidak menetapkan urutan lock bersama; batas cabang merge dapat menulis transaksi cabang lain | Business root lock dahulu, customers/transactions/logs ID menaik; merge admin dibatasi seluruh histori kedua customer; AND-01/13/15, ISO-02 |
| High | Pembayaran parsial/create dapat tercatat dua kali setelah respons hilang walau SUM masih≤total | UUID+hash persisten, replay hasil sama, payload beda409; AND-18 |
| High | State terminal dan batas edit termasuk Rp0/program mati belum sepenuhnya deterministik | Enam transisi sah; dua terminal; semua LUNAS mengunci harga, no-op retries; PRD6.1/FR-A16 |
| High | Manual notification key null, stale job, dan hasil eksternal tidak pasti belum punya kontrak seragam yang tertutup | Semua key non-null; log+job satu commit; lease/token/recovery; unknown terminal tanpa auto resend; AND-03/04/10 |
| High | Pending kuota, retry, penurunan limit dan pergantian bulan dapat berbeda antara slot dan penghitung sukses | Satu slot/key; bulan otorisasi immutable setelah attempt; retry lintas bulan dihentikan; lowering di bawah occupied ditolak; AND-11 |
| High | Expired lifecycle, sesi lama, off→on cepat, serta queued notification sesudah pickup dapat meloloskan kerja usang | Guard per request/preflight, invalidasi pending pada perubahan, reaktivasi menutup pending lama; SEC-08/AND-21 |
| High | Reset email, link wa.me/telpon, role switch dan purge demo tidak tercakup penuh oleh aturan penekanan | Central demo guard, session-bound reserved identities, expiry tepat, purge child-before-parent; AND-12/25/DAT-02 |
| High | Konfigurasi SMTP encrypted JSON tidak cocok dengan ciphertext; WA resmi memerlukan konfigurasi di luar nomor/token | TEXT encrypted:array, wa_config lengkap dengan phone ID/template/version/bahasa atau server/secret; SEC-03/AND-27 |
| High | Data privat berpotensi muncul lewat public print, queue/log/developer observability, transport singleton, cache PWA | DTO terpisah, sanitization, client per tenant, network-only dynamic/no-store; SEC-03/06, ISO-03/05, AND-26 |
| Medium | DP toggle, nama hadiah master/cabang, minimum berat versus hadiah, perubahan N/program, snapshot reprice belum tegas | DP kini memakai setting saat partial pertama dan SUM payment, tanpa snapshot izin (PRD7.4); matching nama normalized, berat diskon aktual, compensation delta asal dan quote fingerprint tetap PRD7.8–7.9 |
| Medium | Ledger nullable terkait transaksi dan detail merge/negative balance tidak cukup kuat | transaction_id wajib, CHECK sign/unique events, owner identity target, saldo SUM bertanda; AND-15/16/23 |
| Medium | Reminder counters/jadwal ketika kanal tidak tersedia, settings berubah, atau scheduler terlambat ambigu | Cursor reservasi persisten, satu nomor/putaran, formula N/M/K eksplisit; AND-20 |
| Medium | Definisi kg/hari/filter/CSV/pendapatan retrospektif dapat berbeda atau terlipat oleh join payment | Payment-date, snapshot baca, sum item terpisah, CSV contract/formula sanitization; AND-24 |
| High | Backup DB tidak mengetahui accepted send setelah titik backup yang hilang karena restore | Hold operasional sebelum provider dapat diakses, rekonsiliasi, cutoff re-enable; tidak menjamin deduplikasi external side effect window RPO; PRD9.2.1, AND-05/27 |
| High | Edit nomor/merge dapat membiarkan WA pending memakai nomor lama yang sudah dipakai customer lain | Never-attempted retarget atomik dengan key/slot sama; pernah-attempt menjadi review tanpa retry/retarget, token dicabut; PRD9.2, AND-28 |
| Low | Terminologi, rujukan bagian, rentang angka, dan konteks PRD hilang usang | Diselaraskan, matriks traceability dan validator disimpan, milestone dibandingkan HEAD |

## Temuan tambahan pada audit kedua

- Urutan purge hasil edit pertama belum menyebut `promos`; diperbaiki dan urutan FK diperiksa secara terstruktur.
- Jalur auth reset tidak dapat memakai notification_logs yang mewajibkan transaction_id; ditetapkan encrypted auth job terpisah, token terbaru, SMTP global, demo suppression.
- Off→on/expired→perpanjang sebelum worker berjalan dapat menghidupkan pending lama; invalidasi dilakukan pada mutator lifecycle/settings, bukan hanya ketika worker kebetulan melihat kondisi off.
- Reminder recovery dengan scanner per menit memerlukan batas sehat enam menit untuk ambang stale lima menit; AC/NFR diperbaiki.
- Retry perlu payload/provider options yang dibekukan dan klasifikasi conservative untuk HTTP5xx/response rusak; kontrak diperjelas.
- Kalimat lama tentang DP, edit Rp0, reset audit, network-first, dan field alias diselaraskan dengan aturan final.

## Model sistem dan invariant

Business memiliki cabang, user, katalog, customer bersama, promo dan settings. Transaksi mengikat satu cabang/customer, menyimpan snapshots, payment append-only dan state histories. Loyalty ledger memiliki delta aktual, cache customer hanya proyeksi SUM. Domain writes serial per tenant melalui MySQL root lock; developer/publik/job masing-masing mempunyai konteks akses sempit. Ready/reminder menghasilkan pekerjaan persisten per kanal, worker memakai lease dan fencing token, lalu hasil provider diterima/ditolak/tidak pasti menentukan terminal/retry. Lifecycle menghentikan write/sends tanpa mematikan akses keamanan atau GET publik tenant nyata. Demo dan PWA memiliki guard komunikasi/retensi/cache tersendiri.

- SUM(payment)≤total; total0=LUNAS tanpa payment0; pickup hanya LUNAS.
- Edit harga hanya DITERIMA belum LUNAS tanpa payment/ledger; snapshot stabil selain edit finansial yang secara eksplisit sah.
- SUM(delta ledger)=stamp_count; compensation kebalikan event asal, bukan konfigurasi sekarang; redemption tidak membuat negatif.
- Semua relasi tenant/branch valid saat commit; shared customer tidak membuka transaksi/ledger cabang lain.
- Satu logical notification/key, satu slot WA/key; unknown tidak diulang otomatis, log/queue tidak memiliki dual-write gap.
- WA belum attempted mengikuti nomor customer terkini; marker null saja tidak cukup, attempt_count harus0. Pernah-attempt + recipient berbeda berhenti pada review; email tetap snapshot, hasil worker bertoken dicabut tidak finalize.
- Partial pertama memerlukan dp_enabled kini; DP berjalan boleh dilanjutkan setelah off. Payment/toggle serial pada root lock, replay tetap idempoten.
- Restore hold memblokir provider sampai rekonsiliasi, cutoff menghalangi replay/catch-up historis; DB consistency tidak membuktikan external side-effect deduplication window RPO.
- State/lifecycle/role diverifikasi ulang di batas mutasi dan preflight; in-flight eksternal tidak dapat ditarik kembali.
- Tidak ada komunikasi eksternal demo, penghapusan histori tenant normal, atau cache browser data tenant.

## Simulasi adversarial yang direview

Delapan kelompok berikut mencatat **64 skenario sistem dari audit sebelumnya**; deskripsi finansial diselaraskan dengan keputusan DP final. Ini audit kontrak melalui penalaran, bukan eksekusi aplikasi atau klaim seluruh audit luas diulang pada sesi focused fix. Rujukan NFR menyediakan ekspektasi tes implementasinya; sepuluh skenario terfokus terbaru ada di bawah.

| Kelompok | Delapan urutan yang diperiksa | Hasil kontrak / tes |
|---|---|---|
| F01–F08 Finansial | lunas penuh; partial pertama ditolak saat setting off; DP yang sudah berjalan tetap sah sesudah off; dua payment melampaui sisa; retry payment parsial; payment vs edit; payment vs batal; total0 tanpa payment | SUM/status/keys tetap konsisten; AND-02/06/13/17/18 |
| L01–L08 Ledger | dua redemption; N berubah lalu refund; program mati sebelum LUNAS; mati saat kompensasi; earning terpakai lalu batal; merge saldo positif; merge saldo negatif; merge bersamaan payment | Delta asal/cache/cabang terjaga; AND-07/15/16/23 |
| S01–S08 State | DITERIMA unpaid edit; DITERIMA DP edit catatan; DITERIMA paid edit harga; DITERIMA0 loyalty off; DIPROSES unpaid payment; SIAP DP pickup; batal sesudah diserahkan; retry/transisi paralel | Hanya aksi matriks6.1; AND-09/14/18 |
| N01–N08 Crash | crash sesudah log sebelum job; commit lalu job hilang; claim sebelum marker; marker sebelum call lalu crash; accepted sebelum finalize lalu crash; timeout tetapi accepted; definite reject tiga panggilan; manual duplicate request | Atomisitas/recovery atau unknown terminal tanpa duplikasi; AND-03/04/10/25 |
| Q01–Q08 Kuota |99/100 dua reservasi; slot pending penuh; retry slot sama; bulan berubah sebelum attempt; accepted melintasi midnight; retry lintas bulan; lowering limit di bawah used; switch off→on sebelum worker | Satu bucket/key dan pending lama tidak direplay; AND-11/20/21 |
| I01–I08 Isolasi | FK lintas tenant; customer ID bisnis lain; ledger link cabang lain; admin merge histori cabang lain; developer failed payload; resi+email milik penyerang; brute force route alternatif; public print full-data | Scope/DTO/authority sempit; ISO-01/02/03/05/06, SEC-02/06/09 |
| B01–B08 Lifecycle/demo | read-only+must-change; nonaktif sesi lama; read-only public POST; batas kalender expiry; branch deactivate vs create; arbitrary demo role ID; demo reset/WA/telpon; expired demo purge dengan FK | Tidak deadlock, tidak outbound/orphan; SEC-08, AND-12/19/25, DAT-02 |
| R01–R08 Laporan/cache | join multiple payments; DP dua bulan; cancel retrospektif; kg aktual vs minimum; batas WIB; master sync snapshot; stale quote; logout/switch/offline browser | Angka/snapshot/privasi konsisten; AND-08/19/24/26 |

Delapan vektor harga tambahan: kg3,5×7000 + item2×25000=74500; kg2 minimum3×7000=21000; hadiah aktual2 dari minimum3 memberi14000; promo minimum90000 pada base80000 ditolak; persen10% base80000=8000; nominal100000 base80000 dibatasi80000; half-up0,5→1; overflow INT ditolak. State-machine juga ditinjau sebagai 25 pasangan:6 transisi,5 same-state no-op,14 ditolak (dengan syarat LUNAS untuk pickup).

## Focused final fixes dan audit kedua dependency berubah

Scope: CustomerService/CustomerMergeService, NotificationDispatcher claim/retry/recovery/finalize, kuota WA, PaymentService, business_settings/transactions/notification_logs, dan prosedur backup/restore. PRD tetap sumber produk; tidak menambah FR, milestone, stack, state transaksi, atau mekanisme ledger. Pemeriksaan di sini merupakan simulasi kontrak, **bukan runtime tests**.

### Hasil sepuluh skenario wajib

| ID | Urutan adversarial | State akhir deterministik menurut kontrak | Trace |
|---|---|---|---|
| FF-01 | WA pending A → edit B → A dipakai customer baru → worker lama/recovery | Bila marker null dan attempt0: tujuan B, key/slot sama, claim lama batal; hanya B yang dapat menerima dari log ini. Customer baru A tidak menjadi lookup transaksi lama. | US-213 AC5,7,9; AND-28 |
| FF-02 | WA pending source A → merge target B | FK/ledger berpindah, tujuan WA B, key tetap; source dihapus terakhir dan A bebas hanya sesudah commit; email tetap snapshot. | US-214 AC6,7,9; AND-15/28 |
| FF-03 | WA provider attempt → merge sebelum finalize → callback accepted/rejected lama | Log perlu_pemeriksaan/recipient_berubah, token null, tujuan/payload/attempt/bucket asal tetap; callback gagal CAS, tanpa retry/retarget. In-flight dapat sudah diterima provider; tidak menjamin penarikan kiriman tersebut. Jika finalize commit sebelum merge, hasil terminal historis tetap, tidak dikirim lagi. | US-214 AC8; US-307 AC9; AND-21/28 |
| FF-04 | Backup01.00 → email accepted10.01 → restore01.00 | Bukti send10.01 tidak ada; hold aktif, SMTP diblokir sampai rekonsiliasi. Tidak mengklaim mengetahui/menjamin deduplikasi send yang hilang. | US-301 AC5; US-308 AC10; AND-05/27 |
| FF-05 | Backup01.00 → WA accepted10.01 → restore01.00 | Bukti send/slot sesudah backup mungkin hilang; hold memblokir WA, tidak mengarang slot atau outcome; re-enable setelah rekonsiliasi dengan limitation RPO eksplisit. | US-307 AC10; US-308 AC10; AND-05/27 |
| FF-06 | Restore → worker/recovery/scheduler hidup sebelum rekonsiliasi | Nol SMTP/WA call, tidak menambah attempt/slot/cursor; log nonterminal existing masuk review dan token dicabut. Auth/manual/verifikasi tidak menjadi bypass; bisnis aman boleh berjalan tanpa backlog outbound baru. | US-101 AC9; US-308 AC11–13; AND-27 |
| FF-07 | DP on saat create → off sebelum partial pertama | SUM=0, partial ditolak; tidak ada payment/ledger baru. Pembayaran penuh tetap boleh. | US-206 AC4,11; AND-17 |
| FF-08 | Total100000, DP30000 → off → partial20000 | Terbayar50000, sisa50000, status DP; setting off tidak memutus DP berjalan. | US-206 AC12; AND-17 |
| FF-09 | Total100000, DP30000 → off → pelunasan70000 | Terbayar100000, status LUNAS; efek loyalti existing hanya jika eligible dan sekali. | US-206 AC13; AND-17 |
| FF-10 | Toggle-off paralel dengan partial pertama | Root lock menentukan urutan: off commit dahulu→partial ditolak; partial commit dahulu→DP tercatat, off berikutnya tidak melarang cicilan selanjutnya. Kedua hasil sah dan tidak overpay. | US-206 AC14,15; AND-01/17/18 |

### Temuan terfokus yang ditutup sebelum verdict

- Marker dikosongkan sesudah definite rejection bukan bukti belum pernah send; attempt_count monoton tetap authority bersama marker, termasuk reclaim/recovery. Retarget tidak menggandakan key/slot/cursor.
- Claim sebelum edit harus dicabut; worker membaca ulang tujuan dari row terkunci. Jika nomor normalized sama, helper tidak mencabut claim berulang. Marker lebih dahulu adalah batas otorisasi in-flight, bukan janji dapat membatalkan provider call.
- Callback accepted terlambat sebelumnya boleh memperbaiki unknown dengan token sama; recipient/restore kini mencabut token sehingga review tidak ditimpa dan slot tidak dilepas. A→B→A tidak revive identity terminal.
- Customer bersama cabang: edit nomor harus memelihara semua log customer satu tenant tanpa membuka data cabang lain; pengecualian internal sempit didokumentasikan. Merge tetap menolak admin yang menyentuh histori cabang lain. Fault adjustment rollback identitas/FK/ledger/log serta pelepasan nomor.
- Re-enable berdasarkan waktu pembuatan log saja masih dapat mengirim reminder historis: cutoff juga memeriksa waktu_siap_diambil untuk ready/reminder, tanpa catch-up/cursor reset. Timestamp sama cutoff diblokir; cutover drain writer dan restart konfigurasi seluruh proses; cutoff tetap tersimpan di deployment saat restart.
- Job auth/reset lama tidak punya notification_log: cleanup job/token lama dan pemeriksaan waktu request existing wajib, transport hold berlaku juga manual/verifikasi/auth. Tidak menambah tabel disaster recovery atau membuka akses operasional developer.
- Payment replay tetap dibedakan dari payment baru; initial payment create memakai aturan runtime yang sama. Total0, overpay, state terminal, payment append-only, ledger dan batas tenant/cabang tetap.

### Review database dan traceability

Hapus `dp_allowed` dari transactions, tanpa field pengganti. Destination adalah kolom existing `tujuan`; gunakan delivery_started_at + attempt_count serta processing_token yang ada. Tidak ada tambahan kolom/index/enum/CHECK/unique constraint. Indeks customer→transaction→log existing cukup; UNIQUE notification_key dan slot per key tetap. Restore hold/cutoff berada di konfigurasi di luar backup DB, bukan tabel aplikasi.

AC baru/diubah terhubung pada FR-A06/O07, A13/A14, N01–N06 dan jalur email terdampak. AND-17 diganti semantik runtime; AND-05/27 mendokumentasikan hold serta limitation RPO; AND-28 baru untuk stale recipient. AND-08/15/21 diselaraskan. Validator tetap memeriksa baseline FR/milestone, GWT, schema, NFR dan purge; ditambah sembilan kontrak trace terfokus, larangan authority DP lama/kolom destination duplikat, serta klausa keselamatan yang harus ada. Ini tidak menggantikan audit semantik atau tes aplikasi.

## Mechanical validation

Jalankan dari root repository:

```bash
python3 docs/audits/validate-final-specs.py
git diff --check
```

Hasil saat penutupan focused fix: 72 FR; 72 punya story dan AC eksplisit; 51 stories/257 AC; 73 NFR/49 [Uji]; 29 kelompok invariant kritis punya [Uji]; 139 referensi kolom qualified cocok schema; sembilan kontrak trace terfokus valid; tidak ada placeholder; semua FR/milestone identik baseline HEAD; matrix references/GWT/link/urutan purge valid; git diff --check lulus. Validator memeriksa referensi qualified secara mekanis; kolom tanpa prefix, nilai derived, bentuk FK/constraint, dan perilaku AC juga direview manual. Jumlah kemunculan ID bukan dasar tunggal verdict.

Enam kontrol negatif validator dijalankan dengan perubahan hanya di memori, tanpa mengubah spesifikasi di disk: reintroduksi field DP lama, hilangnya AC terfokus dari matriks, hilangnya [Uji] AND-28, hilangnya cutoff log, predicate never_attempted diubah AND→OR, dan referensi schema palsu. Keenamnya ditolak dengan exit nonzero serta pesan sesuai. Ini uji tooling dokumen, bukan implementasi domain.

## Batas jaminan dan keputusan produk

Tidak ada ambiguitas produk material yang menunggu keputusan. Trade-off sengaja dan eksplisit: semua Rp0 LUNAS mengunci harga; pembatalan hanya sebelum penyerahan; admin merge dibatasi cabang; email publik hanya transaksi; unknown send tidak diulang otomatis; kuota berdasarkan otorisasi aplikasi; satu business lock menserialkan write; expired demo ditutup tepat waktu dan purge mengikuti SLA scheduler. WA yang sudah diotorisasi/in-flight tidak dapat ditarik, tetapi perubahan nomor menutup retry/retarget berikutnya. Restore hold dan rekonsiliasi tidak mengembalikan pengetahuan external send yang hilang setelah backup; tidak ada jaminan deduplikasi window RPO, exactly-once provider, atau hasil uji runtime yang belum ada.

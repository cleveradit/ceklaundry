# Implementation Plan: TICKET-012 (Direktori dan identitas pelanggan)

**Ticket:** `TICKET-012`
**Status:** `DONE`
**Hasil:** [Audit M2](../audits/m2-verification.md) memuat bukti dan batas verifikasi.
**Target Audience:** AI Developer Agents
**Depends On:** `TICKET-010`
**Tahap:** M2 — pelanggan

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [PRD 5.B/7.9](../initiate-file/prd.md), [US-213](../initiate-file/user-stories.md), [arsitektur 3/5](../initiate-file/architecture.md) |
| Keterlacakan | US-203 AC1/7, US-213 AC1–4; ISO-01/02/04/06, AND-01/08, LOK-04, SEC-05 |
| Identitas | Direktori satu bisnis; `08…`, `+62…`, `62…` menjadi `62…`, unik per bisnis; nama boleh sama. Email master tidak mengubah snapshot transaksi lama. |
| Batas admin | Boleh mencari/mengubah identitas customer lintas cabang dalam bisnis; rincian transaksi/ledger hanya cabangnya. |
| Otorisasi | Pengguna menyetujui implementasi seluruh TICKET-011–020. |

## 2. Objective

Owner dan admin dapat menemukan, menambah, serta memperbaiki identitas pelanggan tanpa memutus hubungan transaksi. Akses identitas bersama tidak membuka rincian operasi cabang lain.

## 3. Non-Negotiable Technical Contract

1. `app/Services/CustomerService.php`: normalisasi/validasi nama, nomor, email; seluruh tulis melalui `BusinessTransaction` dan unique (`business_id`, `no_hp`).
2. `app/Http/Controllers/App/CustomerController.php`, `routes/web.php`: cari/tambah/edit lewat policy tenant; DTO daftar tidak memuat transaksi, payment, log atau ledger cabang lain.
3. `resources/js/Pages/App/Customers.tsx`: pencarian dan form mobile berbahasa Indonesia; tampilkan bahwa edit kontak master tidak mengubah email transaksi lama.
4. `tests/Feature/Operations/CustomerDirectoryTest.php`: normalisasi, collision, rollback, lifecycle, dua tenant/cabang.

## 4. Scope of Changes

1. Manfaatkan tabel/model Customer M1 dan root lock; jangan menerima `business_id` dari payload.
2. Cari nama/no. HP ternormalisasi dengan kueri terikat parameter; nomor panjang/email di luar batas ditolak, tidak dipotong.
3. Siapkan satu titik integrasi pemeliharaan recipient WA untuk M3; jangan mengklaim US-213 AC5–9 lulus sebelum dispatcher M3 nyata.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Nomor setara | `0812…`, `+62812…`, `62812…` | Satu nomor kanonis; duplikat satu bisnis ditolak | Lihat [audit M2](../audits/m2-verification.md) |
| Nama sama | Dua nomor berbeda, nama sama | Dua customer sah | Lihat [audit M2](../audits/m2-verification.md) |
| Edit master | Ganti nomor/email customer bertransaksi | ID dan transaksi tetap; `notification_email` transaksi lama tetap | Lihat [audit M2](../audits/m2-verification.md) |
| Isolasi | Admin cari/customer bisnis lain; detail cabang lain | 404/daftar kosong; tidak ada rincian operasi lintas cabang | Lihat [audit M2](../audits/m2-verification.md) |
| Kegagalan | Nomor tak valid/kolom terlalu panjang, bisnis baca-saja | Validasi/423; tidak ada write parsial | Lihat [audit M2](../audits/m2-verification.md) |

## 6. Verification Commands

```bash
rtk proxy docker compose exec -T app php artisan test --filter=CustomerDirectoryTest
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk git diff --check
```

## 7. Out of Scope

1. Penggabungan customer TICKET-016.
2. Retarget/review log WA M3 dan verifikasi email publik M3; integrasinya harus diuji ulang pada customer edit nyata.

## 8. Completion Checklist

- [x] Otorisasi implementasi, matriks dan verifikasi selesai.
- [x] Isolasi tenant/cabang dan batas integrasi M3 dicatat pada audit M2.

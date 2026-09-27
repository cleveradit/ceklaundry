<?php

return [
    'required' => ':attribute wajib diisi.', 'string' => ':attribute harus berupa teks.',
    'email' => 'Alamat email tidak valid.', 'unique' => ':attribute sudah digunakan.',
    'min' => ['string' => ':attribute minimal :min karakter.', 'numeric' => ':attribute minimal :min.', 'array' => 'Pilih minimal :min data.'],
    'max' => ['string' => ':attribute maksimal :max karakter.', 'numeric' => ':attribute maksimal :max.', 'array' => 'Maksimal :max pilihan.'],
    'size' => ['string' => ':attribute harus berisi :size karakter.'],
    'date_format' => ':attribute harus berupa tanggal yang valid.', 'date' => ':attribute bukan tanggal yang valid.',
    'integer' => ':attribute harus berupa bilangan bulat.', 'numeric' => ':attribute harus berupa angka.',
    'boolean' => ':attribute harus berupa pilihan aktif atau nonaktif.', 'array' => ':attribute harus berupa daftar.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.', 'regex' => 'Format :attribute tidak valid.',
    'decimal' => ':attribute memiliki terlalu banyak angka di belakang koma.', 'in' => 'Pilihan :attribute tidak valid.',
    'accepted' => 'Konfirmasi diperlukan sebelum menyimpan.', 'distinct' => 'Pilihan :attribute tidak boleh berulang.',
    'attributes' => ['nama' => 'Nama', 'email' => 'Email', 'password' => 'Password', 'active_until' => 'Masa aktif', 'branch_id' => 'Cabang', 'branches' => 'Cabang', 'telepon' => 'Nomor telepon', 'alamat' => 'Alamat', 'harga' => 'Harga', 'durasi_jam' => 'Durasi', 'berat_minimum' => 'Berat minimum', 'satuan' => 'Satuan', 'is_active' => 'Status aktif', 'token' => 'Tautan reset'],
];

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow"><meta name="referrer" content="no-referrer">
    <title>Status resi {{ $receipt['kode_resi'] }} — CekLaundry</title>
    @vite('resources/css/public.css')
</head>
<body>
    <header><a class="brand" href="/">CekLaundry<span>STATUS CUCIAN</span></a><a href="/">Cek resi lain →</a></header>
    <main class="status-page">
        @if($receipt['demo']) <p class="demo-note">Ini data demo.</p> @endif
        <span class="eyebrow">RESI {{ $receipt['kode_resi'] }}</span>
        <h1>{{ str_replace('_', ' ', $receipt['status']) }}</h1>
        <p>{{ $receipt['nama_pelanggan'] }} · {{ $receipt['no_hp_pelanggan'] }}</p>
        @if($receipt['stamps'])<section class="public-card"><h2>Stempel Anda: {{ $receipt['stamps']['balance'] < 0 ? '−'.abs($receipt['stamps']['balance']) : $receipt['stamps']['balance'] }}/{{ $receipt['stamps']['target'] }}</h2>@if($receipt['stamps']['balance'] < 0)<p>Stempel yang sudah digunakan perlu diperoleh kembali. Kumpulkan {{ $receipt['stamps']['target'] - $receipt['stamps']['balance'] }} stempel untuk hadiah berikutnya.</p>@else<p>{{ max(0, $receipt['stamps']['target'] - $receipt['stamps']['balance']) }} stempel lagi untuk hadiah berikutnya.</p>@endif</section>@endif
        @if($receipt['status'] === 'DIBATALKAN') <aside><strong>Transaksi dibatalkan.</strong><p>Pengembalian dana, bila ada, ditangani langsung oleh cabang laundry.</p></aside> @endif
        <section class="public-card"><h2>Perjalanan cucian</h2><ol class="timeline">@foreach($receipt['timeline'] as $entry)<li><strong>{{ str_replace('_', ' ', $entry->status) }}</strong><time>{{ \Carbon\Carbon::parse($entry->created_at)->timezone('Asia/Jakarta')->translatedFormat('j F Y H.i') }} WIB</time></li>@endforeach</ol></section>
        <section class="public-card"><h2>Rincian layanan</h2>@foreach($receipt['items'] as $item)<div class="public-row"><span>{{ $item->nama_layanan_snapshot }} · {{ $item->satuan_snapshot === 'kg' ? str_replace('.', ',', $item->berat_kg).' kg' : $item->jumlah_unit.' item' }}{{ $item->is_stamp_reward ? ' · hadiah stempel' : '' }}</span><strong>Rp{{ number_format($item->subtotal, 0, ',', '.') }}</strong></div>@endforeach
            <div class="public-row"><span>Subtotal</span><strong>Rp{{ number_format($receipt['subtotal'], 0, ',', '.') }}</strong></div>
            @if($receipt['potongan_stempel'] > 0)<div class="public-row"><span>Potongan stempel</span><strong>−Rp{{ number_format($receipt['potongan_stempel'], 0, ',', '.') }}</strong></div>@endif
            @if($receipt['potongan_promo'] > 0)<div class="public-row"><span>Promo {{ $receipt['promo_nama_snapshot'] }}</span><strong>−Rp{{ number_format($receipt['potongan_promo'], 0, ',', '.') }}</strong></div>@endif
            <div class="public-row total"><span>Total akhir</span><strong>Rp{{ number_format($receipt['total_akhir'], 0, ',', '.') }}</strong></div>
            <div class="public-row"><span>Terbayar</span><strong>Rp{{ number_format($receipt['terbayar'], 0, ',', '.') }}</strong></div>
            <div class="public-row"><span>Sisa tagihan · {{ str_replace('_', ' ', $receipt['status_bayar']) }}</span><strong>Rp{{ number_format($receipt['sisa'], 0, ',', '.') }}</strong></div>
        </section>
        <section class="public-card"><h2>Waktu & kondisi</h2><p>Masuk: {{ \Carbon\Carbon::parse($receipt['waktu_masuk'])->timezone('Asia/Jakarta')->translatedFormat('j F Y H.i') }} WIB</p><p>Estimasi selesai: {{ \Carbon\Carbon::parse($receipt['estimasi_selesai'])->timezone('Asia/Jakarta')->translatedFormat('j F Y H.i') }} WIB</p>@if($receipt['catatan_kondisi'])<p>Catatan kondisi: {{ $receipt['catatan_kondisi'] }}</p>@endif</section>
        <section class="public-card"><h2>{{ $receipt['cabang']['nama'] }}</h2><p>{{ $receipt['cabang']['alamat'] }}</p><p><a href="tel:{{ $receipt['cabang']['telepon'] }}">Hubungi {{ $receipt['cabang']['telepon'] }}</a></p></section>
        @if($receipt['email_form_enabled'] && in_array($receipt['status'], ['DITERIMA', 'DIPROSES']))
        <section class="public-card"><h2>Notifikasi email</h2><p>Tambahkan alamat email khusus untuk resi ini. Alamat baru berlaku setelah Anda mengonfirmasinya.</p>
            @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
            @if($errors->any())<p role="alert">Permintaan belum dapat diproses. Periksa alamat email.</p>@endif
            <form method="post" action="/t/{{ $receipt['kode_resi'] }}/email">@csrf
                <label for="notification-email">Alamat email</label><input id="notification-email" name="email" type="email" required maxlength="150" autocomplete="email">
                <button class="button" type="submit">Kirim tautan konfirmasi</button>
            </form>
        </section>
        @endif
        <a class="button" href="/t/{{ $receipt['kode_resi'] }}/print">Lihat resi cetak</a>
    </main>
    <footer>CekLaundry · Informasi ini dapat diakses siapa pun yang memegang kode resi.</footer>
</body>
</html>

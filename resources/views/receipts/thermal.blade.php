<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow"><meta name="referrer" content="no-referrer">
    <title>Resi {{ $receipt['kode_resi'] }}</title>
    @vite('resources/css/receipt.css')
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">Cetak resi</button><a href="/t/{{ $receipt['kode_resi'] }}">Lihat status</a></div>
    <main class="receipt">
        <h1>{{ $receipt['cabang']['nama'] }}</h1><p>{{ $receipt['cabang']['alamat'] }}<br>{{ $receipt['cabang']['telepon'] }}</p>
        <div class="divider"></div><h2>{{ $receipt['kode_resi'] }}</h2>
        <p>{{ $receipt['nama_pelanggan'] }} · {{ $receipt['no_hp_pelanggan'] }}</p>
        <p>Masuk {{ $receipt['waktu_masuk'] }} WIB<br>Estimasi {{ $receipt['estimasi_selesai'] }} WIB</p>
        @if($receipt['status'] === 'DIBATALKAN')<p class="cancelled">DIBATALKAN — pengembalian dana di luar aplikasi</p>@endif
        <div class="divider"></div>
        @foreach($receipt['items'] as $item)<div class="line"><span>{{ $item->nama_layanan_snapshot }}{{ $item->is_stamp_reward ? ' · hadiah' : '' }}<small>{{ $item->satuan_snapshot === 'kg' ? str_replace('.', ',', $item->berat_kg).' kg' : $item->jumlah_unit.' item' }} × Rp{{ number_format($item->harga_snapshot, 0, ',', '.') }}</small></span><strong>Rp{{ number_format($item->subtotal, 0, ',', '.') }}</strong></div>@endforeach
        <div class="divider"></div>
        <div class="line"><span>Subtotal</span><strong>Rp{{ number_format($receipt['subtotal'], 0, ',', '.') }}</strong></div>
        @if($receipt['potongan_stempel'] > 0)<div class="line"><span>Stempel</span><strong>−Rp{{ number_format($receipt['potongan_stempel'], 0, ',', '.') }}</strong></div>@endif
        @if($receipt['potongan_promo'] > 0)<div class="line"><span>Promo {{ $receipt['promo_nama_snapshot'] }}</span><strong>−Rp{{ number_format($receipt['potongan_promo'], 0, ',', '.') }}</strong></div>@endif
        <div class="line total"><span>Total akhir</span><strong>Rp{{ number_format($receipt['total_akhir'], 0, ',', '.') }}</strong></div>
        <div class="line"><span>Terbayar</span><strong>Rp{{ number_format($receipt['terbayar'], 0, ',', '.') }}</strong></div>
        <div class="line"><span>Sisa · {{ str_replace('_', ' ', $receipt['status_bayar']) }}</span><strong>Rp{{ number_format($receipt['sisa'], 0, ',', '.') }}</strong></div>
        @if($receipt['catatan_kondisi'])<p>Catatan: {{ $receipt['catatan_kondisi'] }}</p>@endif
        <div class="qr">{!! $qr !!}</div>
        <p class="foot">Cek status di {{ url('/t/'.$receipt['kode_resi']) }}</p>
    </main>
</body>
</html>

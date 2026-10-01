@php
    $receiptRoute = request()->is('t/*') || request()->is('check');
    $missingReceipt = $status === 404 && $receiptRoute;
    $message = match ($status) {
        403 => 'Anda tidak memiliki akses. Akun, cabang, atau bisnis mungkin sudah tidak aktif.',
        404 => $missingReceipt ? 'Kode resi tidak ditemukan, periksa kembali resi Anda' : 'Halaman tidak ditemukan.',
        410 => 'Demo berakhir. Buat demo baru dari halaman depan untuk mencoba lagi.',
        419 => 'Sesi berakhir. Muat ulang halaman dan coba lagi.',
        423 => 'Bisnis saat ini hanya dapat dibaca. Hubungi pengelola.',
        429 => request()->is('demo') ? 'Batas tiga demo per hari tercapai. Coba lagi besok.' : 'Terlalu banyak percobaan. Silakan coba lagi nanti.',
        default => 'Terjadi kendala. Silakan coba lagi.',
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="referrer" content="no-referrer">
    <title>{{ $missingReceipt ? 'Resi tidak ditemukan' : 'Kesalahan '.$status }} — CekLaundry</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: #f7f6f2; color: #1f342e; font: 16px/1.65 system-ui, sans-serif; }
        header, main, footer { max-width: 1100px; margin: auto; padding: 24px; }
        header { border-bottom: 1px solid #d9ded7; }
        .brand { color: #1f342e; font-size: 26px; font-weight: 800; line-height: 1.2; text-decoration: none; }
        .brand span { display: block; font-size: 10px; letter-spacing: 2px; }
        main { max-width: 720px; padding-top: clamp(48px, 10vh, 100px); padding-bottom: 80px; }
        .eyebrow { color: #486156; font-size: 12px; font-weight: 700; letter-spacing: 2px; }
        h1 { margin: 18px 0; font-size: clamp(36px, 7vw, 64px); letter-spacing: -2px; line-height: 1.08; }
        main > p { color: #52665f; font-size: 19px; }
        form { margin: 34px 0 18px; padding: 24px; border: 1px solid #d9e1d8; border-radius: 14px; background: #fff; }
        label { display: block; margin-bottom: 8px; font-weight: 700; }
        .fields { display: flex; gap: 10px; }
        input { flex: 1; min-width: 0; padding: 12px; border: 1px solid #a6b9ad; border-radius: 8px; font: 22px system-ui, sans-serif; letter-spacing: 2px; text-transform: uppercase; }
        button, .button { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 10px 22px; border: 0; border-radius: 8px; background: #12634f; color: #fff; font: 700 16px system-ui, sans-serif; text-decoration: none; cursor: pointer; }
        .back { display: inline-block; margin-top: 14px; color: #12634f; text-underline-offset: 5px; }
        footer { border-top: 1px solid #d9ded7; color: #52665f; font-size: 14px; }
        @media (max-width: 560px) { .fields { display: block; } input, button { width: 100%; } button { margin-top: 10px; } }
    </style>
</head>
<body>
    <header><a class="brand" href="/">CekLaundry<span>CEK CUCIAN ANDA</span></a></header>
    <main>
        <span class="eyebrow">{{ $missingReceipt ? 'STATUS CUCIAN TANPA LOGIN' : 'CEKLAUNDRY' }}</span>
        <h1>{{ $missingReceipt ? 'Resi belum ditemukan.' : $status }}</h1>
        <p role="alert">{{ $message }}</p>
        @if($missingReceipt)
            <form action="/check" method="get">
                <label for="kode_resi">Coba kode resi lain</label>
                <div class="fields">
                    <input id="kode_resi" name="kode_resi" required minlength="6" maxlength="6" pattern="[A-Za-z2-9]{6}" autocomplete="off" placeholder="Contoh K7F3XA">
                    <button type="submit">Cek Status</button>
                </div>
            </form>
            <a class="back" href="/">Kembali ke beranda</a>
        @else
            <a class="button" href="{{ $receiptRoute ? '/' : '/login' }}">Kembali</a>
        @endif
    </main>
    <footer>CekLaundry · Hubungi cabang laundry bila Anda kehilangan resi.</footer>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="referrer" content="no-referrer">
    <title>CekLaundry — Cek status cucian</title>
    @vite('resources/css/public.css')
</head>
<body>
    <header><a class="brand" href="/">CekLaundry<span>CEK CUCIAN ANDA</span></a><a href="/login">Masuk ke panel →</a></header>
    <main class="public-hero">
        <span class="eyebrow">STATUS CUCIAN TANPA LOGIN</span>
        <h1>Cek cucian Anda.</h1>
        <p>Masukkan enam karakter kode resi pada struk untuk melihat progres dan sisa tagihan.</p>
        <form class="lookup-form" action="/check" method="get">
            @if(session('receipt_error'))<p class="lookup-error" role="alert">{{ session('receipt_error') }}</p>@endif
            <label for="kode_resi">Kode resi</label>
            <div><input id="kode_resi" name="kode_resi" value="{{ old('kode_resi') }}" required minlength="6" maxlength="6" pattern="[A-Za-z2-9]{6}" autocomplete="off" placeholder="Contoh K7F3XA" aria-describedby="kode-bantuan"><button type="submit"><span class="lookup-spinner" aria-hidden="true"></span><span class="button-label">Cek Status</span></button></div>
            <small id="kode-bantuan">Kode terdiri dari 6 karakter pada resi.</small>
        </form>
        <button class="demo-note" type="button" disabled aria-disabled="true">Coba Demo · belum tersedia</button>
    </main>
    <footer>CekLaundry · Hubungi cabang laundry bila Anda kehilangan resi.</footer>
    <script>
        (() => {
            const form = document.querySelector('.lookup-form');
            const button = form?.querySelector('button[type="submit"]');
            const label = button?.querySelector('.button-label');
            if (!form || !button || !label) return;
            const reset = () => {
                button.disabled = false;
                button.classList.remove('is-loading');
                button.removeAttribute('aria-busy');
                label.textContent = 'Cek Status';
            };
            form.addEventListener('submit', () => {
                button.disabled = true;
                button.classList.add('is-loading');
                button.setAttribute('aria-busy', 'true');
                label.textContent = 'Mencari...';
            });
            window.addEventListener('pageshow', reset);
        })();
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow"><meta name="referrer" content="no-referrer">
<title>Konfirmasi email — CekLaundry</title>@vite('resources/css/public.css')</head>
<body><header><a class="brand" href="/">CekLaundry<span>KONFIRMASI EMAIL</span></a></header>
<main class="status-page"><section class="public-card"><h1>Konfirmasi email transaksi</h1><p>Konfirmasi alamat untuk resi {{ $code }}. Tautan ini hanya berlaku untuk permintaan terbaru.</p>
<form method="post" action="{{ request()->fullUrl() }}">@csrf<button class="button" type="submit">Konfirmasi email</button></form></section></main></body></html>

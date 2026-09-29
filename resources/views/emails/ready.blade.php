Cucian Anda di {{ $data['branch'] }} ({{ $data['business'] }}) sudah siap diambil.

Kode resi: {{ $data['code'] }}
Total: Rp{{ number_format($data['total'], 0, ',', '.') }}
Sisa tagihan: Rp{{ number_format($data['remaining'], 0, ',', '.') }}
Status terkini: {{ $data['url'] }}

Pengingat dari {{ $data['branch'] }} ({{ $data['business'] }}): cucian Anda siap diambil.

Kode resi: {{ $data['code'] }}
Total: Rp{{ number_format($data['total'], 0, ',', '.') }}
Sisa tagihan: Rp{{ number_format($data['remaining'], 0, ',', '.') }}
Status terkini: {{ $data['url'] }}

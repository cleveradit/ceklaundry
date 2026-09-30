Cucian Anda di {{ $data['branch'] }} ({{ $data['business'] }}) sudah siap diambil.

Kode resi: {{ $data['code'] }}
@if($data['stamp_discount'] > 0)Potongan stempel: Rp{{ number_format($data['stamp_discount'], 0, ',', '.') }}
@endif
@if($data['promo_discount'] > 0)Promo {{ $data['promo_name'] }}: Rp{{ number_format($data['promo_discount'], 0, ',', '.') }}
@endif
Total: Rp{{ number_format($data['total'], 0, ',', '.') }}
Sisa tagihan: Rp{{ number_format($data['remaining'], 0, ',', '.') }}
Status terkini: {{ $data['url'] }}

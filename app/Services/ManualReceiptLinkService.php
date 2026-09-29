<?php

namespace App\Services;

class ManualReceiptLinkService
{
    public function link(object $transaction, string $phone, int $paid, string $type = 'resi'): string
    {
        $statusUrl = url('/t/'.$transaction->kode_resi);
        $text = ($type === 'pengingat' ? 'Pengingat pengambilan CekLaundry ' : 'Resi CekLaundry ').$transaction->kode_resi."\n".
            'Total Rp'.number_format($transaction->total_akhir, 0, ',', '.')."\n".
            'Sisa Rp'.number_format(max(0, $transaction->total_akhir - $paid), 0, ',', '.')."\n".
            'Estimasi '.$transaction->estimasi_selesai." WIB\n".$statusUrl;

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($text);
    }
}

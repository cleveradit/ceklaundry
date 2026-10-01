<?php

namespace App\Services;

class ManualReceiptLinkService
{
    public function link(object $transaction, string $phone, int $paid, string $type = 'resi'): string
    {
        return 'https://wa.me/'.$phone.'?text='.rawurlencode($this->message($transaction, $paid, $type));
    }

    public function message(object $transaction, int $paid, string $type = 'resi'): string
    {
        $statusUrl = url('/t/'.$transaction->kode_resi);

        return ($type === 'pengingat' ? 'Pengingat pengambilan CekLaundry ' : 'Resi CekLaundry ').$transaction->kode_resi."\n".
            'Total Rp'.number_format($transaction->total_akhir, 0, ',', '.')."\n".
            'Sisa Rp'.number_format(max(0, $transaction->total_akhir - $paid), 0, ',', '.')."\n".
            'Estimasi '.$transaction->estimasi_selesai." WIB\n".$statusUrl;
    }
}

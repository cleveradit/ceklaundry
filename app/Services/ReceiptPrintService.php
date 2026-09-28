<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class ReceiptPrintService
{
    public function qr(string $code): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(180), new SvgImageBackEnd));

        return $writer->writeString(url('/t/'.$code));
    }
}

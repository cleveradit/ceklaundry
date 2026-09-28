<?php

namespace Tests\Feature\Operations;

use App\Services\ManualReceiptLinkService;
use Tests\TestCase;

class ManualReceiptLinkTest extends TestCase
{
    public function test_link_contains_current_amount_and_status_url_without_sending(): void
    {
        $transaction = (object) ['kode_resi' => 'ABCD23', 'total_akhir' => 74500, 'estimasi_selesai' => '2026-09-29 12:00:00'];
        $url = app(ManualReceiptLinkService::class)->link($transaction, '628123456789', 30000);
        $this->assertStringStartsWith('https://wa.me/628123456789?text=', $url);
        $message = rawurldecode(parse_url($url, PHP_URL_QUERY));
        $this->assertStringContainsString('Sisa Rp44.500', $message);
        $this->assertStringContainsString('Resi CekLaundry ABCD23', $message);
        $this->assertStringContainsString('/t/ABCD23', $message);
    }
}

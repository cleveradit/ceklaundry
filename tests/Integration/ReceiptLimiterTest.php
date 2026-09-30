<?php

namespace Tests\Integration;

use Tests\FoundationTestCase;

class ReceiptLimiterTest extends FoundationTestCase
{
    public function test_form_and_direct_urls_share_thirty_per_minute_limit(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.155']);
        for ($i = 0; $i < 15; $i++) {
            $this->get('/t/XXXXXX')->assertNotFound();
            $this->get('/check?kode_resi=XXXXXX')->assertRedirect('/');
        }
        $this->get('/t/XXXXXX')->assertStatus(429);
    }
}

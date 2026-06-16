<?php

namespace Tests\Unit;

use App\Services\QrisPayloadService;
use Tests\TestCase;

class QrisPayloadServiceTest extends TestCase
{
    public function test_it_generates_dynamic_qris_payload_with_amount(): void
    {
        config([
            'payment.qris.static_payload' => '00020101021126610014COM.GO-JEK.WWW01189360091437108869220210G7108869220303UMI51440014ID.CO.QRIS.WWW0215ID10254531401270303UMI5204599553033605802ID5919Allegiaant Pet Shop6005BOGOR61051661062070703A0163040DAF',
        ]);

        $payload = app(QrisPayloadService::class)->dynamicPayload(16000);

        $this->assertNotNull($payload);
        $this->assertStringContainsString('010212', $payload);
        $this->assertStringContainsString('540516000', $payload);
        $this->assertMatchesRegularExpression('/6304[A-F0-9]{4}$/', $payload);
    }
}

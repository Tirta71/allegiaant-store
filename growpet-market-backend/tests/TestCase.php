<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payment.pakasir.project_slug' => 'test-project',
            'payment.pakasir.api_key' => 'test-api-key',
            'payment.pakasir.frontend_url' => 'http://localhost:5173',
        ]);

        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY) ?: '', $query);
            $body = $request->data();

            if (str_contains($request->url(), '/api/transactioncreate/qris')) {
                return Http::response([
                    'payment' => [
                        'project' => 'test-project',
                        'order_id' => $body['order_id'] ?? null,
                        'amount' => (int) ($body['amount'] ?? 0),
                        'fee' => 1003,
                        'total_payment' => ((int) ($body['amount'] ?? 0)) + 1003,
                        'payment_method' => 'qris',
                        'payment_number' => '00020101021226610016ID.CO.PAKASIR.WWW0118936009000000000000000000000000000000005204599953033605409101003.005802ID5907Pakasir6012KAB. KEBUMEN6105543926304ABCD',
                        'expired_at' => now()->addMinutes(15)->toISOString(),
                    ],
                ]);
            }

            return Http::response([
                'transaction' => [
                    'amount' => (int) ($query['amount'] ?? 0),
                    'order_id' => $query['order_id'] ?? null,
                    'project' => 'test-project',
                    'status' => config('testing.pakasir_status', 'pending'),
                    'payment_method' => 'qris',
                    'completed_at' => now()->toISOString(),
                ],
            ]);
        });
    }
}

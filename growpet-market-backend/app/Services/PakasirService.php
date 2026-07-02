<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PakasirService
{
    public function __construct(private readonly OrderPaymentConfirmationService $confirmation)
    {
    }

    public function isConfigured(): bool
    {
        return filled($this->projectSlug()) && filled($this->apiKey());
    }

    public function ensureConfigured(): void
    {
        if ($this->isConfigured()) {
            return;
        }

        throw ValidationException::withMessages([
            'payment' => 'Pakasir belum dikonfigurasi. Isi PAKASIR_PROJECT_SLUG dan PAKASIR_API_KEY di backend.',
        ]);
    }

    public function paymentUrl(Order $order): ?string
    {
        if (! filled($this->projectSlug())) {
            return null;
        }

        $query = [
            'order_id' => $order->code,
            'redirect' => $this->redirectUrl($order),
        ];

        if ((bool) config('payment.pakasir.qris_only', true)) {
            $query['qris_only'] = 1;
        }

        return sprintf(
            '%s/pay/%s/%d?%s',
            $this->baseUrl(),
            rawurlencode($this->projectSlug()),
            (int) $order->total,
            http_build_query($query, '', '&', PHP_QUERY_RFC3986)
        );
    }

    public function createQrisTransaction(Order $order): array
    {
        $this->ensureConfigured();

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout(15)
                ->post($this->baseUrl().'/api/transactioncreate/qris', [
                    'project' => $this->projectSlug(),
                    'order_id' => $order->code,
                    'amount' => (int) $order->total,
                    'api_key' => $this->apiKey(),
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Pakasir QRIS transaction create request failed.', [
                'order_code' => $order->code,
                'message' => $this->sanitizeLogMessage($exception->getMessage()),
            ]);

            throw ValidationException::withMessages([
                'payment' => 'Gagal membuat QRIS Pakasir. Coba checkout ulang beberapa saat lagi.',
            ]);
        }

        if (! $response->successful()) {
            Log::warning('Pakasir QRIS transaction create returned non-success response.', [
                'order_code' => $order->code,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            throw ValidationException::withMessages([
                'payment' => 'Gagal membuat QRIS Pakasir. Cek konfigurasi project Pakasir.',
            ]);
        }

        $payment = $response->json('payment');

        if (! is_array($payment) || blank($payment['payment_number'] ?? null)) {
            Log::warning('Pakasir QRIS transaction create returned invalid payment payload.', [
                'order_code' => $order->code,
                'body' => Str::limit($response->body(), 500),
            ]);

            throw ValidationException::withMessages([
                'payment' => 'Response QRIS Pakasir tidak valid.',
            ]);
        }

        $this->assertWebhookMatchesOrder($order, $payment);

        return $payment;
    }

    public function prepareQrisPayment(Order $order): Payment
    {
        $paymentPayload = $this->createQrisTransaction($order);

        $payment = $order->payments()
            ->where('method', Payment::METHOD_PAKASIR)
            ->firstOrFail();

        $payment->update([
            'amount' => (int) $order->total,
            'provider_reference' => $paymentPayload['order_id'] ?? $order->code,
            'provider_payload' => $paymentPayload['payment_number'],
            'provider_fee' => isset($paymentPayload['fee']) ? (int) $paymentPayload['fee'] : null,
            'provider_total' => isset($paymentPayload['total_payment'])
                ? (int) $paymentPayload['total_payment']
                : (int) $order->total,
            'provider_expires_at' => $this->parseProviderDate($paymentPayload['expired_at'] ?? null),
        ]);

        return $payment->refresh();
    }

    public function transactionDetail(Order $order): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->get($this->baseUrl().'/api/transactiondetail', [
                    'project' => $this->projectSlug(),
                    'amount' => (int) $order->total,
                    'order_id' => $order->code,
                    'api_key' => $this->apiKey(),
                ]);
        } catch (\Throwable $exception) {
            Log::warning('Pakasir transaction detail request failed.', [
                'order_code' => $order->code,
                'message' => $this->sanitizeLogMessage($exception->getMessage()),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Pakasir transaction detail returned non-success response.', [
                'order_code' => $order->code,
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return null;
        }

        $transaction = $response->json('transaction');

        return is_array($transaction) ? $transaction : null;
    }

    public function syncOrderPayment(Order $order): Order
    {
        if ($order->status !== Order::STATUS_PENDING_PAYMENT || ! $this->isConfigured()) {
            return $order;
        }

        $payment = $order->payments()
            ->where('method', Payment::METHOD_PAKASIR)
            ->first();

        if (! $payment) {
            return $order;
        }

        $transaction = $this->transactionDetail($order);

        if (! $this->isCompletedTransaction($transaction)) {
            return $order;
        }

        try {
            $this->assertWebhookMatchesOrder($order, $transaction);
        } catch (ValidationException $exception) {
            Log::warning('Pakasir transaction detail did not match local order.', [
                'order_code' => $order->code,
                'errors' => $exception->errors(),
            ]);

            return $order;
        }

        return $this->confirmation->confirm(
            $order,
            $payment,
            'Payment Pakasir completed. Order masuk antrean delivery pet.',
            true
        );
    }

    public function assertWebhookMatchesOrder(Order $order, array $payload): void
    {
        $project = Arr::get($payload, 'project');
        $amount = (int) Arr::get($payload, 'amount');
        $orderId = Arr::get($payload, 'order_id');

        if ($project !== $this->projectSlug()) {
            throw ValidationException::withMessages([
                'project' => 'Project Pakasir tidak cocok.',
            ]);
        }

        if ($orderId !== $order->code || $amount !== (int) $order->total) {
            throw ValidationException::withMessages([
                'payment' => 'Data webhook Pakasir tidak cocok dengan order.',
            ]);
        }
    }

    public function isCompletedTransaction(?array $transaction): bool
    {
        return ($transaction['status'] ?? null) === 'completed';
    }

    private function redirectUrl(Order $order): string
    {
        $frontendUrl = trim((string) config('payment.pakasir.frontend_url', 'http://localhost:5173'));

        return rtrim($frontendUrl, '/').'/cek-transaksi?'.http_build_query([
            'code' => $order->code,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('payment.pakasir.base_url', 'https://app.pakasir.com'), '/');
    }

    private function projectSlug(): ?string
    {
        $slug = config('payment.pakasir.project_slug');

        return filled($slug) ? (string) $slug : null;
    }

    private function apiKey(): ?string
    {
        $apiKey = config('payment.pakasir.api_key');

        return filled($apiKey) ? (string) $apiKey : null;
    }

    private function parseProviderDate(?string $date): ?Carbon
    {
        if (blank($date)) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }

    private function sanitizeLogMessage(string $message): string
    {
        return preg_replace('/api_key=[^&\s]+/', 'api_key=[redacted]', $message) ?? $message;
    }
}

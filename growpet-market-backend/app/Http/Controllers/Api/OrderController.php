<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\OrderReservationService;
use App\Support\OrderPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private readonly OrderReservationService $reservation)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'buyer.roblox_username' => ['required', 'string', 'max:255'],
            'buyer.whatsapp' => ['required', 'string', 'max:40'],
            'buyer.notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', Rule::in([OrderItem::TYPE_PET, OrderItem::TYPE_TOKEN])],
            'items.*.product_variant_id' => ['required_if:items.*.type,pet', 'nullable', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'items.*.product_id' => ['required_if:items.*.type,token', 'nullable', 'integer', 'exists:products,id'],
            'items.*.token_rate_id' => ['required_if:items.*.type,token', 'nullable', 'integer', 'exists:token_rates,id'],
            'items.*.nominal' => ['required_if:items.*.type,token', 'nullable', 'integer', 'min:1'],
        ]);

        $order = DB::transaction(function () use ($data) {
            $reservedAt = now();
            $preparedItems = collect($data['items'])->map(fn (array $item) => $this->reservation->reserveItem($item));
            $subtotal = $preparedItems->sum('line_total');
            $totalItems = $preparedItems->sum('quantity');

            $order = Order::query()->create([
                'code' => $this->makeOrderCode(),
                'buyer_roblox_username' => $data['buyer']['roblox_username'],
                'buyer_whatsapp' => $data['buyer']['whatsapp'],
                'buyer_notes' => $data['buyer']['notes'] ?? null,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'total_items' => $totalItems,
                'status' => Order::STATUS_PENDING_PAYMENT,
                'status_note' => 'Order dibuat. Stok dikunci selama 10 menit menunggu payment.',
                'payment_expires_at' => $reservedAt->copy()->addMinutes(10),
                'stock_reserved_at' => $reservedAt,
            ]);

            $order->items()->createMany($preparedItems->all());
            $order->payments()->create([
                'method' => 'qris',
                'amount' => $order->total,
                'status' => Payment::STATUS_PENDING,
            ]);
            $order->statusHistories()->create([
                'status' => Order::STATUS_PENDING_PAYMENT,
                'note' => 'Order dibuat. Stok dikunci selama 10 menit menunggu payment.',
            ]);

            return $order->load(['items', 'payments']);
        });

        return response()->json([
            'data' => $this->orderPayload($order),
        ], 201);
    }

    public function show(string $code): JsonResponse
    {
        $order = Order::query()
            ->byCode($code)
            ->with(['items', 'payments', 'statusHistories'])
            ->firstOrFail();

        $order = $this->reservation->expireIfNeeded($order);

        return response()->json([
            'data' => $this->orderPayload($order),
        ]);
    }

    public function statusHistory(string $code): JsonResponse
    {
        $order = Order::query()->byCode($code)->firstOrFail();
        $order = $this->reservation->expireIfNeeded($order);

        return response()->json([
            'data' => $order->statusHistories()
                ->oldest()
                ->get()
                ->map(fn ($history) => [
                    'status' => $history->status,
                    'note' => $history->note,
                    'created_at' => $history->created_at?->toISOString(),
                ]),
        ]);
    }

    private function makeOrderCode(): string
    {
        do {
            $code = 'GPM-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
        } while (Order::query()->where('code', $code)->exists());

        return $code;
    }

    private function orderPayload(Order $order): array
    {
        return app(OrderPayload::class)->make($order);
    }
}

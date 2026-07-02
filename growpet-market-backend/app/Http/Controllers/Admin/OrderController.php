<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Services\OrderPaymentConfirmationService;
use App\Services\OrderReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $isRealtime = $request->boolean('realtime');
        $data = $this->indexData($request, includeFilters: !$isRealtime);

        if ($isRealtime) {
            return response()->json([
                'html' => view('admin.orders.partials.history', $data)->render(),
                'latest_order_id' => $data['orders']->first()?->id,
                'total' => $data['orders']->total(),
                'refreshed_at' => now()->toIso8601String(),
            ]);
        }

        return view('admin.orders.index', $data);
    }

    private function indexData(Request $request, bool $includeFilters = true): array
    {
        $orders = Order::query()
            ->with(['items', 'payments'])
            ->when($request->filled('status'), fn($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->string('type')->toString();

                if ($type === 'pet') {
                    $query
                        ->whereHas('items', fn($query) => $query->where('item_type', OrderItem::TYPE_PET))
                        ->whereDoesntHave('items', fn($query) => $query->where('item_type', OrderItem::TYPE_TOKEN));
                }

                if ($type === 'token') {
                    $query
                        ->whereHas('items', fn($query) => $query->where('item_type', OrderItem::TYPE_TOKEN))
                        ->whereDoesntHave('items', fn($query) => $query->where('item_type', OrderItem::TYPE_PET));
                }

                if ($type === 'mixed') {
                    $query
                        ->whereHas('items', fn($query) => $query->where('item_type', OrderItem::TYPE_PET))
                        ->whereHas('items', fn($query) => $query->where('item_type', OrderItem::TYPE_TOKEN));
                }
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%' . $request->string('search')->trim() . '%';

                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', $search)
                        ->orWhere('buyer_roblox_username', 'like', $search)
                        ->orWhere('buyer_whatsapp', 'like', $search);
                });
            })
            ->latest()
            ->paginate(5)
            ->withQueryString();

        $data = [
            'orders' => $orders,
            'statuses' => $this->statuses(),
        ];

        if ($includeFilters) {
            $data['statusFilters'] = $this->statusFilters();
            $data['types'] = [
                'pet' => 'Pet',
                'token' => 'Token',
                'mixed' => 'Campuran',
            ];
        }

        return $data;
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'items.productVariant.mutation', 'payments', 'statusHistories']);

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => $this->statuses(),
        ]);
    }

    public function streamOverlay(): View
    {
        return $this->streamOverlayView(route('admin.orders.stream-overlay.feed'));
    }

    public function streamOverlayFeed(Request $request): JsonResponse
    {
        return $this->streamOverlayFeedResponse($request);
    }

    public function publicStreamOverlay(Request $request): View
    {
        $token = $this->requireStreamOverlayToken($request);

        return $this->streamOverlayView(route('stream.order-overlay.feed', ['token' => $token]));
    }

    public function publicStreamOverlayFeed(Request $request): JsonResponse
    {
        $this->requireStreamOverlayToken($request);

        return $this->streamOverlayFeedResponse($request);
    }

    private function streamOverlayView(string $feedUrl): View
    {
        $latestConfirmedPayment = Payment::query()
            ->where('status', Payment::STATUS_CONFIRMED)
            ->whereNotNull('confirmed_at')
            ->whereHas('order', fn ($query) => $query->where('status', '!=', Order::STATUS_CANCELLED))
            ->latest('confirmed_at')
            ->latest('id')
            ->first(['id', 'confirmed_at']);

        return view('admin.orders.stream-overlay', [
            'feedUrl' => $feedUrl,
            'latestOverlayPaymentId' => $latestConfirmedPayment?->id ?? 0,
            'latestOverlayPaymentTime' => $latestConfirmedPayment?->confirmed_at?->toIso8601String(),
        ]);
    }

    private function streamOverlayFeedResponse(Request $request): JsonResponse
    {
        $afterTime = filled($request->query('after_time'))
            ? Carbon::parse($request->query('after_time'))
            : null;
        $afterId = max(0, (int) $request->query('after_id', 0));
        $payment = Payment::query()
            ->with('order.items')
            ->where('status', Payment::STATUS_CONFIRMED)
            ->whereNotNull('confirmed_at')
            ->whereHas('order', fn ($query) => $query->where('status', '!=', Order::STATUS_CANCELLED))
            ->when($afterTime, function ($query) use ($afterTime, $afterId) {
                $query->where(function ($query) use ($afterTime, $afterId) {
                    $query->where('confirmed_at', '>', $afterTime)
                        ->orWhere(function ($query) use ($afterTime, $afterId) {
                            $query->where('confirmed_at', $afterTime)
                                ->where('id', '>', $afterId);
                        });
                });
            })
            ->latest('confirmed_at')
            ->latest('id')
            ->first();
        $payments = $payment ? collect([$payment]) : collect();

        return response()->json([
            'orders' => $payments->map(function (Payment $payment) {
                $order = $payment->order;

                return [
                    'payment_id' => $payment->id,
                    'confirmed_at' => $payment->confirmed_at?->format('d M Y H:i'),
                    'id' => $order->id,
                    'code' => $order->code,
                    'buyer' => $order->buyer_roblox_username,
                    'total' => $order->total,
                    'total_formatted' => 'Rp ' . number_format($order->total, 0, ',', '.'),
                    'item_summary' => $this->overlayItemSummary($order),
                    'remaining_items' => max(0, $order->items->count() - 1),
                    'created_at' => $order->created_at?->format('d M Y H:i'),
                ];
            })->values(),
            'cursor' => [
                'payment_id' => $payment?->id ?? $afterId,
                'event_time' => $payment?->confirmed_at?->toIso8601String() ?? $afterTime?->toIso8601String(),
                'proof_time' => $payment?->confirmed_at?->toIso8601String() ?? $afterTime?->toIso8601String(),
            ],
            'refreshed_at' => now()->toIso8601String(),
        ]);
    }

    private function requireStreamOverlayToken(Request $request): string
    {
        $configuredToken = (string) config('stream.order_overlay_token');
        $providedToken = (string) $request->query('token', '');

        abort_if($configuredToken === '', 404);
        abort_unless($providedToken !== '' && hash_equals($configuredToken, $providedToken), 403);

        return $providedToken;
    }

    public function updateStatus(
        Request $request,
        Order $order,
        OrderPaymentConfirmationService $confirmation,
        OrderReservationService $reservation
    ): RedirectResponse {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys($this->statuses()))],
            'status_note' => ['nullable', 'string'],
        ]);

        if ($data['status'] === Order::STATUS_PAYMENT_CONFIRMED && $order->status !== Order::STATUS_PAYMENT_CONFIRMED) {
            $confirmation->confirm($order, note: $data['status_note'] ?: 'Payment dikonfirmasi admin.');

            return back()->with('status', 'Payment berhasil dikonfirmasi.');
        }

        if ($data['status'] === Order::STATUS_CANCELLED && $order->status !== Order::STATUS_CANCELLED) {
            $reservation->cancel($order, $data['status_note'] ?: 'Order dibatalkan admin. Stok dikembalikan.');

            return back()->with('status', 'Order berhasil dibatalkan.');
        }

        $order->update([
            'status' => $data['status'],
            'status_note' => $data['status_note'] ?? null,
        ]);

        $order->statusHistories()->create([
            'status' => $data['status'],
            'note' => $data['status_note'] ?? null,
        ]);

        return back()->with('status', 'Status order berhasil diupdate.');
    }

    public function uploadDeliveryProof(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'delivery_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'delivery_proof_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($order->status === Order::STATUS_PENDING_PAYMENT) {
            throw ValidationException::withMessages([
                'delivery_proof' => 'Payment harus dikonfirmasi dulu sebelum upload bukti trade.',
            ]);
        }

        if ($order->status === Order::STATUS_CANCELLED) {
            throw ValidationException::withMessages([
                'delivery_proof' => 'Order yang sudah cancelled tidak bisa menerima bukti trade.',
            ]);
        }

        if (filled($order->delivery_proof_url)) {
            throw ValidationException::withMessages([
                'delivery_proof' => 'Bukti trade sudah pernah diupload.',
            ]);
        }

        DB::transaction(function () use ($data, $order): void {
            $path = $data['delivery_proof']->store("delivery-proofs/{$order->code}", 'public');
            $proofUrl = Storage::disk('public')->url($path);
            $deliveryProofNote = $data['delivery_proof_note'] ?? null;
            $note = $deliveryProofNote ?: 'Bukti trade item telah diupload dan pesanan telah selesai.';

            $order->update([
                'status' => Order::STATUS_DELIVERED,
                'status_note' => $note,
                'delivery_proof_url' => $proofUrl,
                'delivery_proof_uploaded_at' => now(),
                'delivery_proof_note' => $deliveryProofNote,
            ]);

            $order->statusHistories()->create([
                'status' => Order::STATUS_DELIVERED,
                'note' => $note,
            ]);
        });

        return back()->with('status', 'Bukti trade berhasil diupload dan order ditandai delivered.');
    }

    private function statuses(): array
    {
        return [
            Order::STATUS_PENDING_PAYMENT => 'Pending payment',
            Order::STATUS_PAYMENT_CONFIRMED => 'Payment confirmed',
            Order::STATUS_PROCESSING => 'Processing',
            Order::STATUS_DELIVERED => 'Delivered',
            Order::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    private function statusFilters(): array
    {
        $usedStatuses = Order::query()
            ->select('status')
            ->distinct()
            ->pluck('status')
            ->all();

        return array_intersect_key($this->statuses(), array_flip($usedStatuses));
    }

    private function overlayItemSummary(Order $order): string
    {
        $item = $order->items->first();

        if (!$item) {
            return 'Order baru';
        }

        if ($item->item_type === OrderItem::TYPE_TOKEN) {
            return $item->package_label_snapshot
                ?? number_format((int) $item->token_amount_snapshot, 0, ',', '.') . ' token';
        }

        $weight = filled($item->weight_kg_snapshot)
            ? (float) $item->weight_kg_snapshot
            : null;
        $weightLabel = $weight === null
            ? null
            : (fmod($weight, 1.0) === 0.0
                ? number_format($weight, 0, ',', '.')
                : number_format($weight, 2, ',', '.')) . 'kg';
        $mutationLabel = filled($item->mutation_name_snapshot)
            ? 'mutasi ' . $item->mutation_name_snapshot
            : null;

        return implode(' ', array_filter([
            $item->product_name_snapshot,
            $mutationLabel,
            $weightLabel,
        ]));
    }
}

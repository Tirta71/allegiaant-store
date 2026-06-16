<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\OrderPaymentConfirmationService;
use App\Services\OrderReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $statusFilters = $this->statusFilters();
        $orders = Order::query()
            ->with(['items', 'payments'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), function ($query) use ($request) {
                $type = $request->string('type')->toString();

                if ($type === 'pet') {
                    $query
                        ->whereHas('items', fn ($query) => $query->where('item_type', OrderItem::TYPE_PET))
                        ->whereDoesntHave('items', fn ($query) => $query->where('item_type', OrderItem::TYPE_TOKEN));
                }

                if ($type === 'token') {
                    $query
                        ->whereHas('items', fn ($query) => $query->where('item_type', OrderItem::TYPE_TOKEN))
                        ->whereDoesntHave('items', fn ($query) => $query->where('item_type', OrderItem::TYPE_PET));
                }

                if ($type === 'mixed') {
                    $query
                        ->whereHas('items', fn ($query) => $query->where('item_type', OrderItem::TYPE_PET))
                        ->whereHas('items', fn ($query) => $query->where('item_type', OrderItem::TYPE_TOKEN));
                }
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';

                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', $search)
                        ->orWhere('buyer_roblox_username', 'like', $search)
                        ->orWhere('buyer_whatsapp', 'like', $search);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => $this->statuses(),
            'statusFilters' => $statusFilters,
            'types' => [
                'pet' => 'Pet',
                'token' => 'Token',
                'mixed' => 'Campuran',
            ],
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'items.productVariant.mutation', 'payments', 'statusHistories']);

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => $this->statuses(),
        ]);
    }

    public function updateStatus(
        Request $request,
        Order $order,
        OrderPaymentConfirmationService $confirmation,
        OrderReservationService $reservation
    ): RedirectResponse
    {
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
            $note = $deliveryProofNote ?: 'Bukti trade item diupload admin.';

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
}

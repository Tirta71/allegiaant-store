<section class="order-history-list">
    @forelse ($orders as $order)
        @php
            $statusClass = match ($order->status) {
                'delivered' => 'success',
                'payment_confirmed' => 'info',
                'cancelled' => 'danger',
                'processing', 'pending_payment' => 'warning',
                default => 'muted',
            };
            $payment = $order->payments->sortByDesc('created_at')->first();
            $isPakasirPayment = $payment?->method === 'pakasir';
            $hasProof = filled($payment?->proof_url);
            $hasDeliveryProof = filled($order->delivery_proof_url);
            $paymentConfirmed = $payment?->status === 'confirmed' || filled($order->paid_at);
            $canConfirmPayment = !$isPakasirPayment && !$paymentConfirmed && $order->status === 'pending_payment';
            $canResetPaymentProof = $hasProof && !$isPakasirPayment && $canConfirmPayment;
            $canDeliver = in_array($order->status, ['payment_confirmed', 'processing'], true);
            $canUploadDeliveryProof =
                !$hasDeliveryProof &&
                in_array($order->status, ['payment_confirmed', 'processing', 'delivered'], true);
            $previewItems = $order->items->take(3);
            $remainingItems = max(0, $order->items->count() - $previewItems->count());
        @endphp

        <article class="order-history-card">
            <div class="order-history-card__top">
                <div class="order-history-id">
                    <a class="order-code" href="{{ route('admin.orders.show', $order) }}">{{ $order->code }}</a>
                    <span
                        class="status-pill {{ $statusClass }}">{{ $statuses[$order->status] ?? str($order->status)->headline() }}</span>
                    <span class="order-history-time">{{ $order->created_at?->format('d M Y H:i') }}</span>
                </div>
                <div class="order-history-total">
                    <span>Total</span>
                    <strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong>
                </div>
            </div>

            <div class="order-history-card__body">
                <div class="order-history-block">
                    <span class="order-history-label">Username Roblox</span>
                    <span class="order-history-buyer-name">{{ $order->buyer_roblox_username }}</span>
                </div>

                <div class="order-history-block">
                    <span class="order-history-label">WhatsApp</span>
                    <span class="order-history-contact">{{ $order->buyer_whatsapp }}</span>
                </div>

                <div class="order-history-block order-history-block--items">
                    <span class="order-history-label">Pesanan</span>
                    <div class="order-items-list">
                        @foreach ($previewItems as $item)
                            <div class="order-item-line">
                                <div class="order-item-copy">
                                    <span class="order-item-title">{{ $item->product_name_snapshot }}</span>
                                    <span class="order-item-meta">
                                        @if ($item->item_type === 'pet')
                                            {{ $item->mutation_name_snapshot }} /
                                            {{ number_format((float) $item->weight_kg_snapshot, 2, ',', '.') }} kg /
                                            Qty {{ $item->quantity }}
                                        @else
                                            {{ $item->package_label_snapshot ?? number_format($item->token_amount_snapshot, 0, ',', '.') . ' token' }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach

                        @if ($remainingItems > 0)
                            <span class="order-more-text">+{{ $remainingItems }} item lain</span>
                        @endif
                    </div>
                </div>

                <div class="order-history-block">
                    <span class="order-history-label">Payment</span>
                    @if ($hasProof)
                        <button class="proof-link" type="button" data-proof-modal-trigger
                            data-proof-url="{{ $payment->proof_url }}" data-proof-code="{{ $order->code }}"
                            data-proof-label="Bukti payment">
                            Lihat bukti upload
                        </button>
                    @elseif ($isPakasirPayment)
                        <span class="order-history-muted">Pakasir: {{ $payment->provider_reference ?? $order->code }}</span>
                    @else
                        <span class="order-history-muted">Belum diupload</span>
                    @endif
                </div>

                <div class="order-history-block">
                    <span class="order-history-label">Bukti trade</span>
                    @if ($hasDeliveryProof)
                        <button class="proof-link" type="button" data-proof-modal-trigger
                            data-proof-url="{{ $order->delivery_proof_url }}" data-proof-code="{{ $order->code }}"
                            data-proof-label="Bukti trade">
                            Lihat bukti trade
                        </button>
                    @else
                        <span class="order-history-muted">Belum upload</span>
                    @endif
                </div>
            </div>

            @if ($canUploadDeliveryProof)
                <form method="POST" action="{{ route('admin.orders.delivery-proof', $order) }}"
                    class="delivery-proof-form delivery-proof-form--side" enctype="multipart/form-data"
                    data-delivery-proof-form>
                    @csrf
                    <input type="hidden" name="delivery_proof_note"
                        value="Bukti trade item telah diupload dan pesanan telah selesai.">
                    <label class="delivery-proof-dropzone delivery-proof-dropzone--side" data-delivery-proof-dropzone
                        tabindex="0">
                        <span class="delivery-proof-dropzone__title">Upload bukti trade</span>
                        <span class="delivery-proof-dropzone__hint">Klik area ini atau paste screenshot dengan
                            Ctrl+V.</span>
                        <input type="file" name="delivery_proof" accept="image/png,image/jpeg,image/webp" required
                            data-delivery-proof-input>
                    </label>
                    <div class="delivery-proof-preview-box" data-delivery-proof-preview-box>
                        <span data-delivery-proof-placeholder>Preview bukti trade</span>
                        <img class="delivery-proof-paste-preview delivery-proof-paste-preview--side"
                            alt="Preview bukti trade {{ $order->code }}" data-delivery-proof-preview hidden>
                    </div>
                    <button class="button small success" type="submit">Upload trade</button>
                </form>
            @endif

            <div class="order-history-actions">
                @if ($canConfirmPayment)
                    <form method="POST" action="{{ route('admin.orders.update-status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="payment_confirmed">
                        <input type="hidden" name="status_note" value="Payment telah di konfirmasi admin.">
                        <button class="button small success" type="submit">Confirm payment</button>
                    </form>
                    @if ($canResetPaymentProof)
                        <form method="POST" action="{{ route('admin.payments.reset-proof', $payment) }}">
                            @csrf
                            @method('PATCH')
                            <button class="button small secondary" type="submit">Reset bukti</button>
                        </form>
                    @endif
                @elseif ($canDeliver)
                    <form method="POST" action="{{ route('admin.orders.update-status', $order) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="delivered">
                        <input type="hidden" name="status_note" value="Pesanan selesai dan sudah dikirim ke buyer.">
                        <button class="button small" type="submit">Delivered</button>
                    </form>
                @endif

                <a class="button small secondary" href="{{ route('admin.orders.show', $order) }}">Detail</a>
            </div>
        </article>
    @empty
        <div class="panel order-panel-clean">
            <div class="empty-state">
                <h3>Belum ada order</h3>
                <p>Order dari checkout front-end akan muncul otomatis di sini.</p>
            </div>
        </div>
    @endforelse
</section>

@if ($orders->hasPages())
    <nav class="pagination pagination--clean" aria-label="Pagination order">
        @if ($orders->onFirstPage())
            <span class="pagination__button is-disabled">Sebelumnya</span>
        @else
            <a class="pagination__button" href="{{ $orders->previousPageUrl() }}">Sebelumnya</a>
        @endif

        <span class="pagination__meta">
            Halaman {{ $orders->currentPage() }} dari {{ $orders->lastPage() }}
        </span>

        @if ($orders->hasMorePages())
            <a class="pagination__button" href="{{ $orders->nextPageUrl() }}">Berikutnya</a>
        @else
            <span class="pagination__button is-disabled">Berikutnya</span>
        @endif
    </nav>
@endif

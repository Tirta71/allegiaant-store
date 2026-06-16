@extends('admin.layouts.app')

@section('title', 'Order')

@section('content')
    <form class="panel filter-panel" method="GET" id="order-filter-form">
        <div class="filters">
            <label>
                Cari order
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Kode, username, atau WhatsApp"
                    data-auto-submit>
            </label>
            <label>
                Status
                <select name="status" data-auto-submit>
                    <option value="">Semua</option>
                    @foreach ($statusFilters as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Jenis order
                <select name="type" data-auto-submit>
                    <option value="">Semua</option>
                    @foreach ($types as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>
    </form>

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
                $hasProof = filled($payment?->proof_url);
                $hasDeliveryProof = filled($order->delivery_proof_url);
                $paymentConfirmed = $payment?->status === 'confirmed' || filled($order->paid_at);
                $canConfirmPayment = !$paymentConfirmed && $order->status === 'pending_payment';
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
                        <span class="order-history-label">Bukti payment</span>
                        @if ($hasProof)
                            <button class="proof-link" type="button" data-proof-modal-trigger
                                data-proof-url="{{ $payment->proof_url }}" data-proof-code="{{ $order->code }}"
                                data-proof-label="Bukti payment">
                                Lihat bukti upload
                            </button>
                        @else
                            <span class="order-history-muted">Belum diupload</span>
                        @endif

                        {{-- @if ($paymentConfirmed && ($order->paid_at || $payment?->confirmed_at))
                            <span
                                class="order-history-muted">{{ $order->paid_at?->format('d M Y H:i') ?? $payment?->confirmed_at?->format('d M Y H:i') }}</span>
                        @endif --}}
                    </div>

                    <div class="order-history-block">
                        <span class="order-history-label">Bukti trade</span>
                        @if ($hasDeliveryProof)
                            <button class="proof-link" type="button" data-proof-modal-trigger
                                data-proof-url="{{ $order->delivery_proof_url }}" data-proof-code="{{ $order->code }}"
                                data-proof-label="Bukti trade">
                                Lihat bukti trade
                            </button>
                            {{-- @if ($order->delivery_proof_uploaded_at)
                                <span
                                    class="order-history-muted">{{ $order->delivery_proof_uploaded_at?->format('d M Y H:i') }}</span>
                            @endif --}}
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

    <div class="proof-modal" id="proof-modal" aria-hidden="true">
        <div class="proof-modal__backdrop" data-proof-modal-close></div>
        <section class="proof-modal__panel" role="dialog" aria-modal="true" aria-labelledby="proof-modal-title">
            <div class="proof-modal__head">
                <div>
                    <span class="order-history-label">Bukti payment</span>
                    <h2 id="proof-modal-title">Preview upload</h2>
                    <p id="proof-modal-code">-</p>
                </div>
                <button class="button small secondary" type="button" data-proof-modal-close>Tutup</button>
            </div>
            <div class="proof-modal__body">
                <img id="proof-modal-image" src="" alt="Bukti payment order">
            </div>
        </section>
    </div>

    <script>
        (() => {
            const form = document.getElementById('order-filter-form')
            const modal = document.getElementById('proof-modal')
            const modalImage = document.getElementById('proof-modal-image')
            const modalCode = document.getElementById('proof-modal-code')
            const modalLabel = modal?.querySelector('.order-history-label')

            if (form) {
                let timeoutId

                form.querySelectorAll('[data-auto-submit]').forEach((field) => {
                    const eventName = field.tagName === 'INPUT' ? 'input' : 'change'

                    field.addEventListener(eventName, () => {
                        window.clearTimeout(timeoutId)
                        timeoutId = window.setTimeout(() => form.requestSubmit(), field.tagName ===
                            'INPUT' ? 420 : 0)
                    })
                })
            }

            if (!modal || !modalImage || !modalCode) {
                return
            }

            const closeModal = () => {
                modal.setAttribute('aria-hidden', 'true')
                modalImage.removeAttribute('src')
                document.body.classList.remove('is-modal-open')
            }

            document.querySelectorAll('[data-proof-modal-trigger]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    modalImage.src = trigger.dataset.proofUrl || ''
                    modalCode.textContent = trigger.dataset.proofCode || '-'
                    if (modalLabel) {
                        modalLabel.textContent = trigger.dataset.proofLabel || 'Bukti payment'
                    }
                    modal.setAttribute('aria-hidden', 'false')
                    document.body.classList.add('is-modal-open')
                })
            })

            modal.querySelectorAll('[data-proof-modal-close]').forEach((trigger) => {
                trigger.addEventListener('click', closeModal)
            })

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                    closeModal()
                }
            })

            let activeDeliveryProofForm = null

            const setImageFile = (form, file) => {
                if (!form || !file || !file.type.startsWith('image/')) {
                    return
                }

                const input = form.querySelector('[data-delivery-proof-input]')
                const dropzone = form.querySelector('[data-delivery-proof-dropzone]')
                const preview = form.querySelector('[data-delivery-proof-preview]')
                const placeholder = form.querySelector('[data-delivery-proof-placeholder]')

                if (!input) {
                    return
                }

                const files = new DataTransfer()
                const extension = file.type.split('/')[1] || 'png'
                const safeFile = file.name ? file : new File([file], `trade-proof-${Date.now()}.${extension}`, {
                    type: file.type
                })

                files.items.add(safeFile)
                input.files = files.files

                if (preview) {
                    preview.src = URL.createObjectURL(safeFile)
                    preview.hidden = false
                }

                if (placeholder) {
                    placeholder.hidden = true
                }

                dropzone?.classList.add('is-filled')
            }

            document.querySelectorAll('[data-delivery-proof-form]').forEach((deliveryForm) => {
                const input = deliveryForm.querySelector('[data-delivery-proof-input]')
                const dropzone = deliveryForm.querySelector('[data-delivery-proof-dropzone]')

                dropzone?.addEventListener('click', () => {
                    activeDeliveryProofForm = deliveryForm
                })

                dropzone?.addEventListener('focus', () => {
                    activeDeliveryProofForm = deliveryForm
                })

                dropzone?.addEventListener('paste', (event) => {
                    const imageItem = Array.from(event.clipboardData?.items || [])
                        .find((item) => item.type.startsWith('image/'))

                    if (!imageItem) {
                        return
                    }

                    event.preventDefault()
                    setImageFile(deliveryForm, imageItem.getAsFile())
                })

                dropzone?.addEventListener('dragover', (event) => {
                    event.preventDefault()
                    activeDeliveryProofForm = deliveryForm
                    dropzone.classList.add('is-dragging')
                })

                dropzone?.addEventListener('dragleave', () => {
                    dropzone.classList.remove('is-dragging')
                })

                dropzone?.addEventListener('drop', (event) => {
                    event.preventDefault()
                    dropzone.classList.remove('is-dragging')
                    setImageFile(deliveryForm, event.dataTransfer?.files?.[0])
                })

                input?.addEventListener('change', () => {
                    activeDeliveryProofForm = deliveryForm
                    setImageFile(deliveryForm, input.files?.[0])
                })
            })

            document.addEventListener('paste', (event) => {
                if (!activeDeliveryProofForm) {
                    return
                }

                const imageItem = Array.from(event.clipboardData?.items || [])
                    .find((item) => item.type.startsWith('image/'))

                if (!imageItem) {
                    return
                }

                event.preventDefault()
                setImageFile(activeDeliveryProofForm, imageItem.getAsFile())
            })
        })()
    </script>
@endsection

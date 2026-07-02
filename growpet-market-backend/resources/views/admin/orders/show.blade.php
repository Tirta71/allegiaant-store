@extends('admin.layouts.app')

@section('title', 'Detail Order')

@section('content')
    <div class="toolbar">
        <a class="button secondary" href="{{ route('admin.orders.index') }}">Kembali ke daftar order</a>
    </div>

    @php
        $statusClass = match ($order->status) {
            'delivered', 'payment_confirmed' => 'success',
            'cancelled' => 'danger',
            'processing' => 'warning',
            default => '',
        };
    @endphp

    <section class="panel order-detail-hero">
        <div>
            <span class="content-bar__eyebrow">Kode order</span>
            <strong class="order-detail-code">{{ $order->code }}</strong>
            <div class="order-detail-meta">
                <span>{{ $order->buyer_roblox_username }}</span>
                <span>{{ $order->buyer_whatsapp }}</span>
                <span>{{ $order->created_at?->format('d M Y H:i') }}</span>
            </div>
        </div>
        <div class="order-detail-total">
            <span
                class="status-pill {{ $statusClass }}">{{ $statuses[$order->status] ?? str($order->status)->headline() }}</span>
            <strong>Rp {{ number_format($order->total, 0, ',', '.') }}</strong>
            <span class="muted">{{ $order->total_items }} item</span>
        </div>
    </section>

    <section class="info-grid">
        <article class="panel order-panel-clean">
            <h2>Ringkasan transaksi</h2>
            <dl class="detail-list">
                <div>
                    <dt>Subtotal</dt>
                    <dd>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt>Total</dt>
                    <dd>Rp {{ number_format($order->total, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt>Paid at</dt>
                    <dd>{{ $order->paid_at?->format('d M Y H:i') ?? 'Belum paid' }}</dd>
                </div>
                <div>
                    <dt>Status note</dt>
                    <dd>{{ $order->status_note ?? '-' }}</dd>
                </div>
                <div>
                    <dt>Catatan buyer</dt>
                    <dd>{{ $order->buyer_notes ?? '-' }}</dd>
                </div>
            </dl>
        </article>

        <article class="panel order-panel-clean">
            <h2>Update status</h2>
            <form method="POST" action="{{ route('admin.orders.update-status', $order) }}" class="grid"
                style="margin-top: 12px;">
                @csrf
                @method('PATCH')
                <label>
                    Status
                    <select name="status" required>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($order->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    Status note
                    <textarea name="status_note" placeholder="Contoh: Order sedang diproses seller.">{{ old('status_note', $order->status_note) }}</textarea>
                </label>
                <button class="button" type="submit">Simpan status baru</button>
            </form>
        </article>
    </section>

    <section class="panel order-section order-panel-clean">
        <div class="section-head">
            <div>
                <h2>Item order</h2>
            </div>
            <span class="status-pill muted">{{ $order->total_items }} item</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Detail</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>

                                <span
                                    class="type-pill {{ $item->item_type }}">{{ $item->item_type === 'pet' ? 'Pet' : 'Token' }}</span>
                            </td>
                            <td>
                                @if ($item->item_type === 'pet')
                                    {{ $item->mutation_name_snapshot }} -
                                    {{ number_format((float) $item->weight_kg_snapshot, 2, ',', '.') }} kg
                                @else
                                    {{ $item->package_label_snapshot }} - rate Rp
                                    {{ number_format($item->token_rate_snapshot, 0, ',', '.') }}/1K
                                @endif
                            </td>
                            <td>{{ $item->quantity }}</td>
                            <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td><strong>Rp {{ number_format($item->line_total, 0, ',', '.') }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="detail-grid" style="margin-top: 16px;">
        <article class="panel order-panel-clean">
            <h2>Payment</h2>
            <div class="table-wrap">
                <table class="compact-table" style="margin-top: 12px;">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Provider / Bukti</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->payments as $payment)
                            @php
                                $isPakasirPayment = $payment->method === 'pakasir';
                                $paymentConfirmed = $payment->status === 'confirmed' || filled($order->paid_at);
                                $canConfirmPayment = ! $isPakasirPayment
                                    && ! $paymentConfirmed
                                    && $order->status === 'pending_payment';
                            @endphp
                            <tr>
                                <td>{{ $payment->method }}</td>
                                <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                <td><span
                                        class="status-pill {{ $payment->status === 'confirmed' ? 'success' : 'muted' }}">{{ str($payment->status)->headline() }}</span>
                                </td>
                                <td>
                                    @if ($payment->proof_url)
                                        <a class="button small secondary" href="{{ $payment->proof_url }}" target="_blank"
                                            rel="noreferrer">Lihat bukti</a>
                                    @elseif ($isPakasirPayment)
                                        <span class="muted">{{ $payment->provider_reference ?? $order->code }}</span>
                                    @else
                                        <span class="muted">Belum upload</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($canConfirmPayment)
                                        <div class="actions">
                                            <form method="POST" action="{{ route('admin.payments.confirm', $payment) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="button small" type="submit">Confirm payment</button>
                                            </form>
                                            @if ($payment->proof_url)
                                                <form method="POST" action="{{ route('admin.payments.reset-proof', $payment) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="button small secondary" type="submit">Reset bukti</button>
                                                </form>
                                            @endif
                                        </div>
                                    @elseif ($paymentConfirmed)
                                        <span class="muted">{{ $payment->confirmed_at?->format('d M Y H:i') ?? 'Sudah paid' }}</span>
                                    @elseif ($isPakasirPayment)
                                        <span class="muted">Menunggu gateway</span>
                                    @else
                                        <span class="muted">Menunggu bukti</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="muted">Belum ada payment.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        @php
            $hasDeliveryProof = filled($order->delivery_proof_url);
            $canUploadDeliveryProof = ! $hasDeliveryProof
                && in_array($order->status, ['payment_confirmed', 'processing', 'delivered'], true);
        @endphp

        <article class="panel order-panel-clean">
            <div class="section-head">
                <div>
                    <h2>Bukti trade</h2>
                    <p class="muted">Upload screenshot item sudah diberikan ke user.</p>
                </div>
                @if ($order->delivery_proof_uploaded_at)
                    <span class="status-pill success">{{ $order->delivery_proof_uploaded_at->format('d M Y H:i') }}</span>
                @endif
            </div>

            @if ($hasDeliveryProof)
                <a class="delivery-proof-preview" href="{{ $order->delivery_proof_url }}" target="_blank" rel="noreferrer">
                    <img src="{{ $order->delivery_proof_url }}" alt="Bukti trade order {{ $order->code }}">
                </a>
                @if ($order->delivery_proof_note)
                    <p class="muted" style="margin-top: 10px;">{{ $order->delivery_proof_note }}</p>
                @endif
            @else
                <div class="delivery-proof-empty" data-delivery-proof-empty>
                    <strong>Belum ada bukti trade</strong>
                    <span>Upload file atau paste screenshot Windows langsung di area bawah.</span>
                </div>
            @endif

            @if ($canUploadDeliveryProof)
                <form method="POST" action="{{ route('admin.orders.delivery-proof', $order) }}" class="delivery-proof-form"
                    enctype="multipart/form-data" data-delivery-proof-form>
                    @csrf
                    <label class="delivery-proof-dropzone" data-delivery-proof-dropzone tabindex="0">
                        <span class="delivery-proof-dropzone__title">Paste screenshot atau pilih file</span>
                        <span class="delivery-proof-dropzone__hint">Klik area ini, lalu tekan Ctrl+V. JPG, PNG, WEBP max 8 MB.</span>
                        <input id="delivery-proof-input" type="file" name="delivery_proof" accept="image/png,image/jpeg,image/webp"
                            required data-delivery-proof-input>
                    </label>
                    <img class="delivery-proof-paste-preview" alt="Preview bukti trade" data-delivery-proof-preview hidden>
                    <label>
                        Catatan bukti
                        <textarea name="delivery_proof_note" placeholder="Contoh: Item sudah trade ke username buyer.">{{ old('delivery_proof_note', $order->delivery_proof_note) }}</textarea>
                    </label>
                    <button class="button success" type="submit">Upload bukti trade</button>
                </form>
            @else
                <p class="muted" style="margin-top: 14px;">
                    Bukti trade bisa diupload setelah payment dikonfirmasi atau order masuk processing.
                </p>
            @endif
        </article>

        <article class="panel order-panel-clean">
            <h2>Status history</h2>
            <div class="timeline">
                @forelse ($order->statusHistories as $history)
                    <div class="timeline-item">
                        <div class="timeline-content">
                            <strong>{{ str($history->status)->headline() }}</strong><br>
                            <span class="muted">{{ $history->created_at?->format('d M Y H:i') }}</span>
                            <p>{{ $history->note ?? '-' }}</p>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <h3>Belum ada histori</h3>
                        <p>Status update akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>
        </article>
    </section>

    <script>
        (() => {
            const form = document.querySelector('[data-delivery-proof-form]')

            if (!form) {
                return
            }

            const input = form.querySelector('[data-delivery-proof-input]')
            const dropzone = form.querySelector('[data-delivery-proof-dropzone]')
            const preview = form.querySelector('[data-delivery-proof-preview]')
            const emptyState = document.querySelector('[data-delivery-proof-empty]')

            const setImageFile = (file) => {
                if (!file || !file.type.startsWith('image/')) {
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

                if (emptyState) {
                    emptyState.hidden = true
                }

                dropzone?.classList.add('is-filled')
            }

            document.addEventListener('paste', (event) => {
                const imageItem = Array.from(event.clipboardData?.items || [])
                    .find((item) => item.type.startsWith('image/'))

                if (!imageItem) {
                    return
                }

                event.preventDefault()
                setImageFile(imageItem.getAsFile())
            })

            input.addEventListener('change', () => {
                setImageFile(input.files?.[0])
            })

            dropzone?.addEventListener('dragover', (event) => {
                event.preventDefault()
                dropzone.classList.add('is-dragging')
            })

            dropzone?.addEventListener('dragleave', () => {
                dropzone.classList.remove('is-dragging')
            })

            dropzone?.addEventListener('drop', (event) => {
                event.preventDefault()
                dropzone.classList.remove('is-dragging')
                setImageFile(event.dataTransfer?.files?.[0])
            })
        })()
    </script>
@endsection

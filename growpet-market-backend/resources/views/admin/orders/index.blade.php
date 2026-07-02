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

    <div class="actions order-overlay-actions">
        <a class="button secondary" href="{{ route('admin.orders.stream-overlay') }}" target="_blank" rel="noreferrer">
            Buka overlay stream
        </a>
        <a class="button soft" href="{{ route('admin.orders.stream-overlay', ['preview' => 1]) }}" target="_blank"
            rel="noreferrer">
            Preview overlay
        </a>
    </div>

    <div id="order-history-realtime" data-order-history-realtime>
        @include('admin.orders.partials.history', ['orders' => $orders, 'statuses' => $statuses])
    </div>

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
            const historyRoot = document.querySelector('[data-order-history-realtime]')
            const modal = document.getElementById('proof-modal')
            const modalImage = document.getElementById('proof-modal-image')
            const modalCode = document.getElementById('proof-modal-code')
            const modalLabel = modal?.querySelector('.order-history-label')
            let activeDeliveryProofForm = null
            let isRefreshing = false

            const closeModal = () => {
                if (!modal || !modalImage) {
                    return
                }

                modal.setAttribute('aria-hidden', 'true')
                modalImage.removeAttribute('src')
                document.body.classList.remove('is-modal-open')
            }

            const openProofModal = (trigger) => {
                if (!modal || !modalImage || !modalCode) {
                    return
                }

                modalImage.src = trigger.dataset.proofUrl || ''
                modalCode.textContent = trigger.dataset.proofCode || '-'
                if (modalLabel) {
                    modalLabel.textContent = trigger.dataset.proofLabel || 'Bukti payment'
                }
                modal.setAttribute('aria-hidden', 'false')
                document.body.classList.add('is-modal-open')
            }

            modal?.querySelectorAll('[data-proof-modal-close]').forEach((trigger) => {
                trigger.addEventListener('click', closeModal)
            })

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal?.getAttribute('aria-hidden') === 'false') {
                    closeModal()
                }
            })

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

            const bindDeliveryProofForms = (scope = document) => {
                scope.querySelectorAll('[data-delivery-proof-form]:not([data-delivery-proof-bound])').forEach((
                    deliveryForm) => {
                    deliveryForm.dataset.deliveryProofBound = 'true'
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
            }

            const hasSelectedDeliveryProof = () => Array.from(
                historyRoot?.querySelectorAll('[data-delivery-proof-input]') || []
            ).some((input) => input.files?.length)

            const buildRealtimeUrl = (url = window.location.href) => {
                const realtimeUrl = new URL(url, window.location.origin)
                realtimeUrl.searchParams.set('realtime', '1')

                return realtimeUrl
            }

            const cleanUrl = (url) => {
                const clean = new URL(url, window.location.origin)
                clean.searchParams.delete('realtime')

                return clean
            }

            const refreshOrderHistory = async (url = window.location.href, options = {}) => {
                if (!historyRoot || isRefreshing || document.hidden) {
                    return
                }

                if (!options.force && (modal?.getAttribute('aria-hidden') === 'false' || hasSelectedDeliveryProof())) {
                    return
                }

                isRefreshing = true

                try {
                    const response = await fetch(buildRealtimeUrl(url), {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    })

                    if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                        return
                    }

                    const payload = await response.json()

                    if (typeof payload.html === 'string') {
                        historyRoot.innerHTML = payload.html
                        activeDeliveryProofForm = null
                        bindDeliveryProofForms(historyRoot)
                    }

                    if (options.push) {
                        window.history.pushState({}, '', cleanUrl(url))
                    }
                } catch {
                    // Keep the page usable if the session expires or the network blips.
                } finally {
                    isRefreshing = false
                }
            }

            historyRoot?.addEventListener('click', (event) => {
                const proofTrigger = event.target.closest('[data-proof-modal-trigger]')

                if (proofTrigger) {
                    openProofModal(proofTrigger)
                    return
                }

                const paginationLink = event.target.closest('.pagination a')

                if (paginationLink) {
                    event.preventDefault()
                    refreshOrderHistory(paginationLink.href, {
                        force: true,
                        push: true,
                    })
                }
            })

            bindDeliveryProofForms(historyRoot || document)

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

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    refreshOrderHistory(window.location.href, {
                        force: true,
                    })
                }
            })

            window.addEventListener('popstate', () => {
                refreshOrderHistory(window.location.href, {
                    force: true,
                })
            })

            window.setInterval(() => refreshOrderHistory(), 7000)
        })()
    </script>
@endsection

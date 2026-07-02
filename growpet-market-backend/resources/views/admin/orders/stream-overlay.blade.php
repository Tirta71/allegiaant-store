<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Stream Overlay</title>
    <style>
        :root {
            --ink: #142019;
            --leaf: #3d6f1d;
            --leaf-soft: #eaf6d3;
            --gold: #f4c95d;
            --line: rgba(48, 73, 35, .18);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            overflow: hidden;
            background: transparent;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--ink);
        }

        body {
            pointer-events: none;
        }

        .overlay-stage {
            position: fixed;
            inset: 0;
            display: grid;
            place-items: end center;
            padding: 42px;
        }

        .order-alert {
            width: min(620px, calc(100vw - 32px));
            border: 1px solid rgba(255, 255, 255, .65);
            border-radius: 16px;
            padding: 8px;
            background:
                linear-gradient(135deg, rgba(255, 255, 255, .97), rgba(243, 250, 231, .95)),
                radial-gradient(circle at top right, rgba(188, 231, 93, .24), transparent 36%);
            box-shadow: 0 24px 68px rgba(20, 32, 25, .2);
            transform: translateY(36px) scale(.96);
            opacity: 0;
            animation: alert-in .55s cubic-bezier(.2, .9, .2, 1) forwards;
        }

        .order-alert.is-leaving {
            animation: alert-out .4s ease forwards;
        }

        .order-alert__inner {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 16px;
            align-items: center;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 18px 20px;
            background: rgba(255, 255, 255, .72);
            backdrop-filter: blur(14px);
        }

        .order-alert__copy {
            min-width: 0;
        }

        .order-alert__eyebrow {
            display: inline-flex;
            align-items: center;
            min-height: 26px;
            border: 1px solid rgba(61, 111, 29, .18);
            border-radius: 999px;
            padding: 5px 9px;
            background: var(--leaf-soft);
            color: #2f5d16;
            font-size: 11px;
            font-weight: 650;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .order-alert h1 {
            margin: 9px 0 5px;
            color: var(--ink);
            font-size: clamp(25px, 3vw, 36px);
            line-height: 1.04;
            font-weight: 650;
            letter-spacing: 0;
        }

        .order-alert p {
            margin: 0;
            color: #53604f;
            font-size: clamp(14px, 1.45vw, 18px);
            line-height: 1.35;
        }

        .order-alert__item {
            margin-top: 10px;
            color: #2a3a27;
            font-weight: 600;
        }

        .order-alert__side {
            display: grid;
            justify-items: end;
            gap: 7px;
            min-width: 150px;
        }

        .order-alert__total {
            color: var(--leaf);
            font-size: clamp(22px, 2.4vw, 32px);
            font-weight: 750;
            line-height: 1;
            text-shadow: 0 1px 0 rgba(255, 255, 255, .8);
            white-space: nowrap;
        }

        .order-alert__code {
            border-radius: 999px;
            padding: 6px 10px;
            background: rgba(244, 201, 93, .24);
            color: #6b4c05;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 12px;
            font-weight: 650;
            white-space: nowrap;
        }

        @keyframes alert-in {
            0% {
                opacity: 0;
                transform: translateY(42px) scale(.94);
                filter: blur(6px);
            }

            70% {
                opacity: 1;
                transform: translateY(-8px) scale(1.01);
                filter: blur(0);
            }

            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
                filter: blur(0);
            }
        }

        @keyframes alert-out {
            to {
                opacity: 0;
                transform: translateY(26px) scale(.97);
                filter: blur(5px);
            }
        }

        @media (max-width: 620px) {
            .overlay-stage {
                padding: 18px;
            }

            .order-alert__inner {
                grid-template-columns: 1fr;
                gap: 14px;
                padding: 14px;
            }

            .order-alert__side {
                grid-template-columns: 1fr auto;
                justify-items: start;
                align-items: center;
                min-width: 0;
            }
        }
    </style>
</head>

<body data-feed-url="{{ $feedUrl }}"
    data-latest-overlay-payment-id="{{ $latestOverlayPaymentId }}"
    data-latest-overlay-payment-time="{{ $latestOverlayPaymentTime }}"
    data-preview="{{ request()->boolean('preview') ? '1' : '0' }}">
    <main class="overlay-stage" data-overlay-stage aria-live="polite"></main>

    <script>
        (() => {
            const stage = document.querySelector('[data-overlay-stage]')
            const feedUrl = document.body.dataset.feedUrl
            const previewMode = document.body.dataset.preview === '1'
            const queue = []
            let latestOverlayPaymentId = Number(document.body.dataset.latestOverlayPaymentId || 0)
            let latestOverlayPaymentTime = document.body.dataset.latestOverlayPaymentTime || ''
            let isFetching = false
            let isShowing = false
            let cachedVoice = null

            const escapeHtml = (value) => {
                const element = document.createElement('span')
                element.textContent = value ?? ''

                return element.innerHTML
            }

            const wait = (duration) => new Promise((resolve) => window.setTimeout(resolve, duration))

            const getVoices = () => new Promise((resolve) => {
                const synth = window.speechSynthesis

                if (!synth) {
                    resolve([])
                    return
                }

                const voices = synth.getVoices()

                if (voices.length) {
                    resolve(voices)
                    return
                }

                window.setTimeout(() => resolve(synth.getVoices()), 350)
            })

            const selectVoice = async () => {
                if (cachedVoice) {
                    return cachedVoice
                }

                const voices = await getVoices()
                const isIndonesian = (voice) => voice.lang?.toLowerCase().startsWith('id')
                const isFemaleName = (voice) => {
                    const name = voice.name?.toLowerCase() || ''

                    return [
                        'gadis',
                        'female',
                        'perempuan',
                        'wanita',
                        'woman',
                        'girl',
                        'sari',
                        'citra',
                        'ayu',
                        'diah',
                        'wulan',
                        'ratna',
                    ].some((keyword) => name.includes(keyword))
                }
                const isGoogle = (voice) => voice.name?.toLowerCase().includes('google')

                cachedVoice =
                    voices.find((voice) => isIndonesian(voice) && isFemaleName(voice)) ||
                    voices.find((voice) => isIndonesian(voice) && isGoogle(voice)) ||
                    voices.find((voice) => isFemaleName(voice)) ||
                    voices.find(isIndonesian) ||
                    voices.find(isGoogle) ||
                    null

                return cachedVoice
            }

            const speechText = (order) => {
                const buyer = order.buyer || 'Buyer'
                const item = order.item_summary || 'order baru'
                const total = order.total_formatted || ''

                return `Pesanan baru dari ${buyer}. ${item}. Total ${total}.`
            }

            const speakOrder = async (order) => {
                const synth = window.speechSynthesis

                if (!synth || !window.SpeechSynthesisUtterance) {
                    return
                }

                const utterance = new SpeechSynthesisUtterance(speechText(order))
                const voice = await selectVoice()

                if (voice) {
                    utterance.voice = voice
                    utterance.lang = voice.lang || 'id-ID'
                } else {
                    utterance.lang = 'id-ID'
                }

                utterance.rate = 1
                utterance.pitch = 1.08
                utterance.volume = 1
                synth.cancel()
                synth.speak(utterance)
            }

            const createAlert = (order) => {
                const remainingText = Number(order.remaining_items || 0) > 0 ?
                    ` +${order.remaining_items} item lain` :
                    ''

                const article = document.createElement('article')
                article.className = 'order-alert'
                article.innerHTML = `
                    <div class="order-alert__inner">
                        <div class="order-alert__copy">
                            <span class="order-alert__eyebrow">Pesanan baru masuk</span>
                            <h1>${escapeHtml(order.buyer || 'Buyer')}</h1>
                            <p class="order-alert__item">${escapeHtml(order.item_summary || 'Order baru')}${escapeHtml(remainingText)}</p>
                            <p>Terima kasih sudah order di Allegiaant Pet Shop.</p>
                        </div>
                        <div class="order-alert__side">
                            <strong class="order-alert__total">${escapeHtml(order.total_formatted || '')}</strong>
                            <span class="order-alert__code">${escapeHtml(order.code || '')}</span>
                        </div>
                    </div>
                `

                return article
            }

            const showNext = async () => {
                if (isShowing || !queue.length || !stage) {
                    return
                }

                isShowing = true
                const order = queue.shift()
                const alert = createAlert(order)
                stage.replaceChildren(alert)
                speakOrder(order)

                await wait(6200)
                alert.classList.add('is-leaving')
                await wait(460)
                alert.remove()
                isShowing = false
                showNext()
            }

            const fetchOrders = async () => {
                if (isFetching || document.hidden) {
                    return
                }

                isFetching = true

                try {
                    const url = new URL(feedUrl, window.location.origin)

                    if (latestOverlayPaymentTime) {
                        url.searchParams.set('after_time', latestOverlayPaymentTime)
                    }

                    url.searchParams.set('after_id', String(latestOverlayPaymentId))

                    const response = await fetch(url, {
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
                    const orders = Array.isArray(payload.orders) ? payload.orders : []

                    if (orders.length) {
                        queue.length = 0
                        queue.push(orders[orders.length - 1])
                    }

                    if (payload.cursor) {
                        latestOverlayPaymentId = Number(payload.cursor.payment_id || latestOverlayPaymentId)
                        latestOverlayPaymentTime = payload.cursor.event_time || payload.cursor.proof_time || latestOverlayPaymentTime
                    }

                    showNext()
                } catch {
                    // Overlay should fail quietly on stream; the next poll will retry.
                } finally {
                    isFetching = false
                }
            }

            if (previewMode) {
                queue.push({
                    id: 1,
                    payment_id: latestOverlayPaymentId + 1,
                    code: 'GPM-PREVIEW',
                    buyer: 'ALLEGIAANT2',
                    total_formatted: 'Rp 15.000',
                    item_summary: 'Raccoon mutasi Venom 60kg',
                    remaining_items: 1,
                })
                showNext()
            }

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    fetchOrders()
                }
            })

            window.setInterval(fetchOrders, 4500)
        })()
    </script>
</body>

</html>

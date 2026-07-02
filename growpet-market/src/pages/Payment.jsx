import { useCallback, useEffect, useRef, useState } from 'react'
import QRCode from 'qrcode'
import { Link, useNavigate } from 'react-router-dom'
import OrderSummary from '../components/cart/OrderSummary'
import Button from '../components/ui/Button'
import PageHeader from '../components/ui/PageHeader'
import { useAlert } from '../context/useAlert'
import { useCart } from '../context/useCart'
import { formatPrice } from '../data/pets'
import { cancelOrder, fetchOrder } from '../features/orders/orders.api'
import {
  clearPendingOrder,
  getCheckTransactionPath,
  getPendingOrder,
  isOrderCheckoutBlocked,
  isOrderFinished,
  savePendingOrder,
} from '../utils/transactions'

const PAYMENT_STATUS_POLL_INTERVAL_MS = 5000

function formatPaymentTime(seconds) {
  const safeSeconds = Math.max(0, seconds)
  const minutes = Math.floor(safeSeconds / 60)
  const remainingSeconds = safeSeconds % 60

  return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`
}

function Payment() {
  const { clearCart } = useCart()
  const { showAlert } = useAlert()
  const navigate = useNavigate()
  const [pendingOrder, setPendingOrder] = useState(() => getPendingOrder())
  const [transaction, setTransaction] = useState(null)
  const [isRefreshingOrder, setIsRefreshingOrder] = useState(() =>
    Boolean(getPendingOrder()?.code),
  )
  const [isCancelling, setIsCancelling] = useState(false)
  const [secondsRemaining, setSecondsRemaining] = useState(0)
  const [hasSyncedTimer, setHasSyncedTimer] = useState(false)
  const [autoCancelStarted, setAutoCancelStarted] = useState(false)
  const [qrState, setQrState] = useState({ payload: '', imageUrl: '', error: '' })
  const clearedCartOrder = useRef('')

  const order = transaction || pendingOrder
  const qrisPayload = order?.paymentInstructions?.qrisPayload || ''
  const qrImageUrl = qrState.payload === qrisPayload ? qrState.imageUrl : ''
  const qrError = qrState.payload === qrisPayload ? qrState.error : ''
  const totalPayment =
    order?.paymentInstructions?.totalPayment ||
    order?.paymentInstructions?.amount ||
    order?.summary?.total ||
    0
  const providerFee = order?.paymentInstructions?.fee || 0
  const paymentMethodLabel = order?.paymentInstructions?.qrisOnly
    ? 'QRIS'
    : 'Payment Gateway'
  const shouldRedirectToCheckTransaction =
    isOrderCheckoutBlocked(order) && order?.rawStatus !== 'pending_payment'
  const isCancelled = transaction?.rawStatus === 'cancelled'
  const checkTransactionPath = order?.code ? getCheckTransactionPath(order) : ''

  const handleCancelOrder = useCallback(async (reason, automatic = false) => {
    if (!pendingOrder?.code) {
      return
    }

    if (automatic) {
      setAutoCancelStarted(true)
    }

    setIsCancelling(true)

    try {
      const cancelledOrder = await cancelOrder(
        pendingOrder.code,
        reason || 'Pesanan dibatalkan buyer. Stok dikembalikan.',
      )
      clearPendingOrder()
      setTransaction(cancelledOrder)
      setPendingOrder(null)
      showAlert({
        tone: automatic ? 'warning' : 'success',
        title: automatic ? 'Waktu payment habis' : 'Pesanan dibatalkan',
        message: 'Stok sudah dikembalikan. Kamu bisa checkout lagi.',
      })
    } catch (requestError) {
      showAlert({
        tone: 'error',
        title: 'Gagal membatalkan',
        message: requestError.message,
      })
    } finally {
      setIsCancelling(false)
    }
  }, [pendingOrder, showAlert])

  useEffect(() => {
    if (pendingOrder?.code && clearedCartOrder.current !== pendingOrder.code) {
      clearedCartOrder.current = pendingOrder.code
      clearCart()
    }
  }, [clearCart, pendingOrder?.code])

  useEffect(() => {
    if (!pendingOrder?.code || transaction) {
      return undefined
    }

    let isActive = true
    const loadingTimer = window.setTimeout(() => {
      if (isActive) {
        setIsRefreshingOrder(true)
      }
    }, 0)

    fetchOrder(pendingOrder.code)
      .then((freshOrder) => {
        if (isActive) {
          if (isOrderFinished(freshOrder)) {
            clearPendingOrder()
            setPendingOrder(null)
            return
          }

          if (freshOrder.rawStatus !== 'pending_payment') {
            savePendingOrder(freshOrder)
            setPendingOrder(freshOrder)
            return
          }

          setPendingOrder(freshOrder)
          savePendingOrder(freshOrder)
        }
      })
      .catch(() => undefined)
      .finally(() => {
        if (isActive) {
          window.clearTimeout(loadingTimer)
          setIsRefreshingOrder(false)
        }
      })

    return () => {
      isActive = false
      window.clearTimeout(loadingTimer)
    }
  }, [pendingOrder?.code, transaction])

  useEffect(() => {
    if (
      !pendingOrder?.code ||
      pendingOrder.rawStatus !== 'pending_payment' ||
      transaction
    ) {
      return undefined
    }

    let isActive = true
    let isPolling = false

    async function pollPaymentStatus() {
      if (isPolling || document.hidden) {
        return
      }

      isPolling = true

      try {
        const freshOrder = await fetchOrder(pendingOrder.code)

        if (!isActive) {
          return
        }

        if (isOrderFinished(freshOrder)) {
          clearPendingOrder()
          setPendingOrder(null)
          return
        }

        savePendingOrder(freshOrder)
        setPendingOrder(freshOrder)

        if (freshOrder.rawStatus !== 'pending_payment') {
          showAlert({
            tone: 'success',
            title: 'Payment berhasil',
            message: 'Status payment sudah otomatis terkonfirmasi.',
          })
        }
      } catch {
        // Keep the QRIS page usable when a transient polling request fails.
      } finally {
        isPolling = false
      }
    }

    const timer = window.setInterval(
      pollPaymentStatus,
      PAYMENT_STATUS_POLL_INTERVAL_MS,
    )

    return () => {
      isActive = false
      window.clearInterval(timer)
    }
  }, [
    pendingOrder?.code,
    pendingOrder?.rawStatus,
    showAlert,
    transaction,
  ])

  useEffect(() => {
    if (!pendingOrder?.paymentExpiresAt || transaction) {
      const resetTimer = window.setTimeout(() => {
        setSecondsRemaining(0)
        setHasSyncedTimer(false)
      }, 0)

      return () => window.clearTimeout(resetTimer)
    }

    function syncRemainingTime() {
      const expiresAt = new Date(pendingOrder.paymentExpiresAt).getTime()
      const remaining = Math.ceil((expiresAt - Date.now()) / 1000)
      setSecondsRemaining(Math.max(0, remaining))
      setHasSyncedTimer(true)
    }

    syncRemainingTime()
    const timer = window.setInterval(syncRemainingTime, 1000)

    return () => window.clearInterval(timer)
  }, [pendingOrder?.paymentExpiresAt, transaction])

  useEffect(() => {
    if (
      !pendingOrder?.code ||
      transaction ||
      !hasSyncedTimer ||
      secondsRemaining > 0 ||
      autoCancelStarted ||
      isRefreshingOrder
    ) {
      return
    }

    const cancelTimer = window.setTimeout(() => {
      handleCancelOrder(
        'Waktu payment 10 menit habis. Pesanan otomatis dibatalkan dan stok dikembalikan.',
        true,
      )
    }, 0)

    return () => window.clearTimeout(cancelTimer)
  }, [
    autoCancelStarted,
    hasSyncedTimer,
    isRefreshingOrder,
    handleCancelOrder,
    pendingOrder,
    secondsRemaining,
    transaction,
  ])

  useEffect(() => {
    if (!checkTransactionPath || !shouldRedirectToCheckTransaction) {
      return
    }

    navigate(checkTransactionPath, { replace: true })
  }, [navigate, checkTransactionPath, shouldRedirectToCheckTransaction])

  useEffect(() => {
    let isActive = true

    if (!qrisPayload) {
      return () => {
        isActive = false
      }
    }

    QRCode.toDataURL(qrisPayload, {
      errorCorrectionLevel: 'M',
      margin: 2,
      width: 320,
      color: {
        dark: '#111827',
        light: '#ffffff',
      },
    })
      .then((url) => {
        if (isActive) {
          setQrState({ payload: qrisPayload, imageUrl: url, error: '' })
        }
      })
      .catch(() => {
        if (isActive) {
          setQrState({
            payload: qrisPayload,
            imageUrl: '',
            error: 'QRIS belum bisa dibuat.',
          })
        }
      })

    return () => {
      isActive = false
    }
  }, [qrisPayload])

  if (!pendingOrder && !transaction) {
    return (
      <div className="container page-flow">
        <section className="empty-state">
          <h1>Tidak ada order payment</h1>
          <p>Buat order dari checkout terlebih dahulu.</p>
          <Button as={Link} to="/">
            Kembali ke market
          </Button>
        </section>
      </div>
    )
  }

  if (shouldRedirectToCheckTransaction) {
    return null
  }

  return (
    <div className="container page-flow">
      <PageHeader
        eyebrow="Payment"
        title={isCancelled ? 'Pesanan dibatalkan' : 'Selesaikan payment QRIS'}
        description={
          isCancelled
            ? 'Stok sudah dikembalikan dan kamu bisa checkout lagi.'
            : 'Scan QRIS sesuai total payment yang tampil di halaman ini.'
        }
      />

      <section className="payment-layout">
        <div className="payment-card">
          {isCancelled ? (
            <>
              <p className="payment-note">
                Stok sudah dikembalikan. Kamu bisa membuat pesanan baru dari market.
              </p>
              <div className="payment-actions">
                <Button as={Link} to="/">
                  Kembali ke market
                </Button>
              </div>
            </>
          ) : (
            <>
              <div className="payment-method">
                <span>{paymentMethodLabel}</span>
                <strong>{formatPrice(totalPayment)}</strong>
              </div>
              <div className={`payment-timer ${secondsRemaining <= 60 ? 'is-warning' : ''}`}>
                <span>Batas payment</span>
                <strong>{formatPaymentTime(secondsRemaining)}</strong>
              </div>
              {order.statusNote && (
                <div className="payment-status-note">
                  <span>Status</span>
                  <p>{order.statusNote}</p>
                </div>
              )}
              <div className="pakasir-panel">
                <span>Kode transaksi</span>
                <strong>{order.code}</strong>
                <p>
                  {isRefreshingOrder
                    ? 'Memuat data payment terbaru...'
                    : qrisPayload
                    ? 'Status payment akan otomatis terkonfirmasi setelah pembayaran berhasil.'
                    : 'QRIS belum tersedia. Coba refresh halaman atau hubungi seller.'}
                </p>
                {providerFee > 0 ? (
                  <small>
                    Order {formatPrice(order.summary.total)} + fee payment {formatPrice(providerFee)}
                  </small>
                ) : null}
              </div>
              <div className="qris-frame" aria-label="QRIS payment">
                {qrImageUrl ? (
                  <img
                    src={qrImageUrl}
                    alt="QRIS payment"
                    className="qris-image"
                  />
                ) : (
                  <div className="qris-placeholder">
                    <span>QRIS</span>
                    <small>QRIS belum tersedia</small>
                  </div>
                )}
              </div>
              <p className="payment-note">
                Scan QRIS ini dari aplikasi e-wallet atau mobile banking. Status akan otomatis berubah setelah payment terkonfirmasi.
              </p>
              {qrError ? <p className="payment-error">{qrError}</p> : null}
              <div className="payment-actions">
                <Button
                  as={Link}
                  to={checkTransactionPath}
                  variant="secondary"
                  onClick={() => {
                    if (order) {
                      savePendingOrder(order)
                    }
                  }}
                >
                  Cek status
                </Button>
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => handleCancelOrder()}
                  disabled={isCancelling}
                >
                  {isCancelling ? 'Membatalkan...' : 'Batalkan pesanan'}
                </Button>
              </div>
            </>
          )}
        </div>

        <OrderSummary
          items={order.items}
          showCheckout={false}
          summary={order.summary}
        />
      </section>
    </div>
  )
}

export default Payment

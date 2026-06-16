import { useEffect, useState } from 'react'
import QRCode from 'qrcode'
import { Link } from 'react-router-dom'
import OrderSummary from '../components/cart/OrderSummary'
import Button from '../components/ui/Button'
import SectionHeader from '../components/ui/SectionHeader'
import { useAlert } from '../context/useAlert'
import { useCart } from '../context/useCart'
import { formatPrice } from '../data/pets'
import { cancelOrder, fetchOrder, uploadPaymentProof } from '../services/api'
import {
  clearPendingOrder,
  getPendingOrder,
  savePendingOrder,
} from '../utils/transactions'

function formatPaymentTime(seconds) {
  const safeSeconds = Math.max(0, seconds)
  const minutes = Math.floor(safeSeconds / 60)
  const remainingSeconds = safeSeconds % 60

  return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`
}

function Payment() {
  const { clearCart } = useCart()
  const { showAlert } = useAlert()
  const [pendingOrder, setPendingOrder] = useState(() => getPendingOrder())
  const [transaction, setTransaction] = useState(null)
  const [copied, setCopied] = useState(false)
  const [proofFile, setProofFile] = useState(null)
  const [qrImageUrl, setQrImageUrl] = useState('')
  const [qrError, setQrError] = useState('')
  const [isRefreshingOrder, setIsRefreshingOrder] = useState(false)
  const [isUploading, setIsUploading] = useState(false)
  const [isCancelling, setIsCancelling] = useState(false)
  const [secondsRemaining, setSecondsRemaining] = useState(0)
  const [hasSyncedTimer, setHasSyncedTimer] = useState(false)
  const [autoCancelStarted, setAutoCancelStarted] = useState(false)

  const order = transaction || pendingOrder
  const qrisPayload = order?.paymentInstructions?.qrisPayload || ''
  const staticQrisImage = order?.paymentInstructions?.staticImageUrl || ''
  const hasUploadedProof = Boolean(order?.payments?.some((payment) => payment.proofUrl))
  const isWaitingAdmin = order?.rawStatus === 'pending_payment' && hasUploadedProof
  const isCancelled = transaction?.rawStatus === 'cancelled'
  const canPay = pendingOrder?.rawStatus === 'pending_payment' && secondsRemaining > 0

  useEffect(() => {
    if (!pendingOrder?.code || transaction) {
      return undefined
    }

    let isActive = true
    setIsRefreshingOrder(true)

    fetchOrder(pendingOrder.code)
      .then((freshOrder) => {
        if (isActive) {
          setPendingOrder(freshOrder)
          savePendingOrder(freshOrder)
        }
      })
      .catch(() => undefined)
      .finally(() => {
        if (isActive) {
          setIsRefreshingOrder(false)
        }
      })

    return () => {
      isActive = false
    }
  }, [pendingOrder?.code, transaction])

  useEffect(() => {
    if (!pendingOrder?.paymentExpiresAt || transaction) {
      setSecondsRemaining(0)
      setHasSyncedTimer(false)
      return undefined
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

    handleCancelOrder(
      'Waktu payment 10 menit habis. Pesanan otomatis dibatalkan dan stok dikembalikan.',
      true,
    )
  }, [
    autoCancelStarted,
    hasSyncedTimer,
    isRefreshingOrder,
    pendingOrder,
    secondsRemaining,
    transaction,
  ])

  useEffect(() => {
    let isActive = true

    setQrImageUrl('')
    setQrError('')

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
          setQrImageUrl(url)
        }
      })
      .catch(() => {
        if (isActive) {
          setQrError('QRIS dinamis belum bisa dibuat.')
        }
      })

    return () => {
      isActive = false
    }
  }, [qrisPayload])

  async function handleUploadProof(event) {
    event.preventDefault()

    if (!pendingOrder) {
      return
    }

    if (!canPay) {
      showAlert({
        tone: 'warning',
        title: 'Waktu payment habis',
        message: 'Pesanan ini sudah tidak bisa dibayar. Buat checkout baru.',
      })
      return
    }

    if (!proofFile) {
      showAlert({
        tone: 'warning',
        title: 'Bukti payment belum dipilih',
        message: 'Upload screenshot atau file bukti payment terlebih dahulu.',
      })
      return
    }

    setIsUploading(true)

    try {
      const updatedOrder = await uploadPaymentProof(pendingOrder.code, proofFile)
      clearCart()
      savePendingOrder(updatedOrder)
      setTransaction(updatedOrder)
      setPendingOrder(updatedOrder)
      showAlert({
        tone: 'success',
        title: 'Bukti payment terkirim',
        message: `Kode ${updatedOrder.code} menunggu pengecekan admin.`,
      })
    } catch (requestError) {
      showAlert({
        tone: 'error',
        title: 'Upload gagal',
        message: requestError.message,
      })
    } finally {
      setIsUploading(false)
    }
  }

  async function handleCancelOrder(reason, automatic = false) {
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
  }

  async function handleCopyCode() {
    if (!order?.code) {
      return
    }

    if (!navigator.clipboard) {
      showAlert({
        tone: 'warning',
        title: 'Clipboard tidak tersedia',
        message: 'Salin kode transaksi secara manual.',
      })
      return
    }

    try {
      await navigator.clipboard.writeText(order.code)
      setCopied(true)
      showAlert({
        tone: 'success',
        title: 'Kode dicopy',
        message: `${order.code} siap ditempel.`,
      })
    } catch {
      showAlert({
        tone: 'error',
        title: 'Copy gagal',
        message: 'Salin kode transaksi secara manual.',
      })
    }
  }

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

  return (
    <div className="container page-flow">
      <SectionHeader
        eyebrow="Payment"
        title={
          isCancelled
            ? 'Pesanan dibatalkan'
            : transaction || isWaitingAdmin
            ? 'Bukti payment terkirim'
            : 'Selesaikan payment QRIS'
        }
        description={
          isCancelled
            ? 'Stok sudah dikembalikan dan kamu bisa checkout lagi.'
            : transaction || isWaitingAdmin
            ? 'Admin akan mengecek bukti payment dan memproses order setelah valid.'
            : 'Scan QRIS sesuai total order, lalu upload bukti payment untuk dicek admin.'
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
          ) : transaction || isWaitingAdmin ? (
            <>
              <p className="payment-label">Kode transaksi</p>
              <div className="transaction-code-row">
                <div className="transaction-code">{order.code}</div>
                <Button variant="secondary" onClick={handleCopyCode}>
                  {copied ? 'Copied' : 'Copy'}
                </Button>
              </div>
              <div className="status-note" data-status={order.rawStatus}>
                <span>Catatan status</span>
                <p>
                  {order.statusNote ||
                    'Simpan kode ini untuk cek status transaksi di halaman cek transaksi.'}
                </p>
              </div>
              <div className="payment-actions">
                {!isCancelled && (
                  <Button as={Link} to="/cek-transaksi">
                    Cek transaksi
                  </Button>
                )}
                <Button as={Link} to="/" variant="ghost">
                  Kembali ke market
                </Button>
              </div>
            </>
          ) : (
            <>
              <div className="payment-method">
                <span>{order.paymentInstructions?.merchantName || 'QRIS / E-wallet'}</span>
                <strong>{formatPrice(order.summary.total)}</strong>
              </div>
              <div className={`payment-timer ${secondsRemaining <= 60 ? 'is-warning' : ''}`}>
                <span>Batas payment</span>
                <strong>{formatPaymentTime(secondsRemaining)}</strong>
              </div>
              <div className="qris-frame" aria-label="QRIS payment">
                {qrImageUrl || staticQrisImage ? (
                  <img
                    src={qrImageUrl || staticQrisImage}
                    alt="QRIS payment"
                    className="qris-image"
                  />
                ) : (
                  <div className="qris-placeholder">
                    <span>QRIS</span>
                    <small>QR belum dikonfigurasi</small>
                  </div>
                )}
              </div>
              <p className="payment-note">
                {isRefreshingOrder
                  ? 'Memuat data payment terbaru...'
                  : qrisPayload
                  ? 'Nominal sudah tertanam di QRIS dinamis. Pembeli tinggal scan dan bayar.'
                  : 'Scan QRIS statis, lalu pastikan nominal sama dengan total order.'}
              </p>
              {qrError ? <p className="payment-error">{qrError}</p> : null}
              <form className="payment-upload" onSubmit={handleUploadProof}>
                <label className="proof-field">
                  <span>Upload bukti payment</span>
                  <input
                    type="file"
                    accept="image/png,image/jpeg,image/webp,application/pdf"
                    onChange={(event) => setProofFile(event.target.files?.[0] || null)}
                  />
                  <small>
                    {proofFile
                      ? proofFile.name
                      : 'JPG, PNG, WEBP, atau PDF. Maksimal 8 MB.'}
                  </small>
                </label>
                <Button type="submit" disabled={isUploading || !proofFile || !canPay}>
                  {isUploading ? 'Mengupload...' : 'Upload bukti payment'}
                </Button>
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => handleCancelOrder()}
                  disabled={isCancelling || isUploading}
                >
                  {isCancelling ? 'Membatalkan...' : 'Batalkan pesanan'}
                </Button>
              </form>
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

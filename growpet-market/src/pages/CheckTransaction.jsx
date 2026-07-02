import { useCallback, useEffect, useRef, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import Alert from '../components/ui/Alert'
import Button from '../components/ui/Button'
import { useAlert } from '../context/useAlert'
import { formatPrice, formatWeight } from '../data/pets'
import { fetchOrder } from '../features/orders/orders.api'
import {
  clearPendingOrder,
  getPendingOrder,
  isOrderCheckoutBlocked,
  savePendingOrder,
  shouldContinuePayment,
} from '../utils/transactions'

const SELLER_WHATSAPP_DISPLAY = '0812-8496-4533'
const SELLER_WHATSAPP_URL = 'https://wa.me/6281284964533'
const PRIVATE_SERVER_NAME = 'ALLEGIAANT2'
const PRIVATE_SERVER_URL =
  'https://www.roblox.com/share?code=11816dc15902fb4bb2989f795b25f5e1&type=Server'
const PRIVATE_SERVER_VISIBLE_STATUSES = new Set([
  'payment_confirmed',
  'processing',
  'delivered',
])
const REVIEW_POLL_INTERVAL_MS = 12000
const DELIVERY_POLL_INTERVAL_MS = 15000

function formatDate(value) {
  if (!value) {
    return '-'
  }

  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

function getSellerWhatsappUrl(transactionCode) {
  const message = transactionCode
    ? `Halo seller, saya mau tanya status transaksi ${transactionCode}.`
    : 'Halo seller, saya mau tanya order Growpet Market.'

  return `${SELLER_WHATSAPP_URL}?text=${encodeURIComponent(message)}`
}

function CheckTransaction() {
  const { showAlert } = useAlert()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const [code, setCode] = useState('')
  const [transaction, setTransaction] = useState(null)
  const [hasSearched, setHasSearched] = useState(false)
  const [copied, setCopied] = useState(false)
  const [checkedCode, setCheckedCode] = useState('')
  const [isSearching, setIsSearching] = useState(false)
  const autoCheckedCode = useRef('')
  const sellerWhatsappUrl = getSellerWhatsappUrl(transaction?.code || checkedCode)
  const canOpenPrivateServer = transaction
    ? PRIVATE_SERVER_VISIBLE_STATUSES.has(transaction.rawStatus)
    : false

  const checkOrder = useCallback(async (nextCode, options = {}) => {
    const { silent = false } = options
    const nextCheckedCode = nextCode.trim().toUpperCase()

    if (!nextCheckedCode) {
      return
    }

    if (!silent) {
      setIsSearching(true)
    }

    try {
      const foundTransaction = await fetchOrder(nextCheckedCode)

      if (shouldContinuePayment(foundTransaction)) {
        const needsProofReupload = String(foundTransaction.statusNote || '')
          .toLowerCase()
          .includes('belum valid')

        savePendingOrder(foundTransaction)
        if (!silent) {
          showAlert({
            tone: 'warning',
            title: needsProofReupload ? 'Bukti perlu upload ulang' : 'Payment masih tertunda',
            message: needsProofReupload
              ? foundTransaction.statusNote
              : 'Selesaikan atau batalkan payment dulu sebelum cek transaksi.',
          })
        }
        navigate('/payment', { replace: true })
        return
      }

      setTransaction(foundTransaction)
      setHasSearched(true)
      setCopied(false)
      setCheckedCode(nextCheckedCode)
      if (isOrderCheckoutBlocked(foundTransaction)) {
        savePendingOrder(foundTransaction)
      } else {
        clearPendingOrder()
      }
      if (!silent) {
        showAlert({
          tone: 'success',
          title: 'Transaksi ditemukan',
          message: `${foundTransaction.code} sedang diproses.`,
        })
      }
    } catch (requestError) {
      if (!silent) {
        setTransaction(null)
        setHasSearched(true)
        setCopied(false)
        setCheckedCode(nextCheckedCode)
        showAlert({
          tone: 'warning',
          title: 'Kode tidak ditemukan',
          message:
            requestError.status === 404
              ? 'Cek lagi kode transaksi dari backend.'
              : requestError.message,
        })
      }
    } finally {
      if (!silent) {
        setIsSearching(false)
      }
    }
  }, [navigate, showAlert])

  useEffect(() => {
    const urlCode = searchParams.get('code')?.trim().toUpperCase() || ''
    const pendingCode = getPendingOrder()?.code?.trim().toUpperCase() || ''
    const autoCode = urlCode || pendingCode

    if (!autoCode || autoCheckedCode.current === autoCode) {
      return
    }

    autoCheckedCode.current = autoCode
    setCode(autoCode)
    checkOrder(autoCode)
  }, [checkOrder, searchParams])

  useEffect(() => {
    if (
      !transaction?.code ||
      !isOrderCheckoutBlocked(transaction) ||
      shouldContinuePayment(transaction)
    ) {
      return undefined
    }

    const intervalMs =
      transaction.rawStatus === 'pending_payment'
        ? REVIEW_POLL_INTERVAL_MS
        : DELIVERY_POLL_INTERVAL_MS
    const timer = window.setInterval(() => {
      checkOrder(transaction.code, { silent: true })
    }, intervalMs)

    return () => window.clearInterval(timer)
  }, [checkOrder, transaction])

  async function handleSubmit(event) {
    event.preventDefault()
    await checkOrder(code)
  }

  async function handleCopyCode() {
    if (!transaction) {
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
      await navigator.clipboard.writeText(transaction.code)
      setCopied(true)
      showAlert({
        tone: 'success',
        title: 'Kode dicopy',
        message: `${transaction.code} siap ditempel.`,
      })
    } catch {
      showAlert({
        tone: 'error',
        title: 'Copy gagal',
        message: 'Salin kode transaksi secara manual.',
      })
    }
  }

  return (
    <div className="container page-flow">
      <section className="market-intro transaction-hero">
        <div>
          <p className="market-kicker">Cek transaksi</p>
          <h1>Lacak order pakai kode transaksi</h1>
          <p>
            Masukkan kode order dari payment untuk melihat status terbaru dan
            membuka shortcut chat seller.
          </p>
        </div>
        <div className="market-stats" aria-label="Cek transaksi highlights">
          <div>
            <strong>Kode</strong>
            <span>Order</span>
          </div>
          <div>
            <strong>Status</strong>
            <span>Update</span>
          </div>
          <div>
            <strong>Chat</strong>
            <span>Seller</span>
          </div>
        </div>
      </section>

      <section className="transaction-layout">
        <form className="transaction-form" onSubmit={handleSubmit}>
          <label>
            <span>Kode transaksi</span>
            <input
              type="text"
              value={code}
              onChange={(event) => setCode(event.target.value)}
              placeholder="GPM-20260614-ABCDE"
              required
            />
          </label>
          <Button type="submit" disabled={isSearching}>
            {isSearching ? 'Mengecek...' : 'Cek status'}
          </Button>
        </form>

        {hasSearched && (
          <section className="seller-contact" aria-label="Kontak seller">
            <div>
              <span>Kontak seller</span>
              <strong>WhatsApp {SELLER_WHATSAPP_DISPLAY}</strong>
              <p>
                Butuh bantuan cek order atau delivery? Chat seller langsung
                dengan kode {transaction?.code || checkedCode}.
              </p>
            </div>
            <Button
              as="a"
              href={sellerWhatsappUrl}
              target="_blank"
              rel="noreferrer"
              variant="secondary"
            >
              Chat WhatsApp
            </Button>
          </section>
        )}

        {hasSearched && transaction && (
          <section className="private-server-card" aria-label="Private server">
            <div>
              <span>Private server</span>
              {canOpenPrivateServer ? (
                <>
                  <strong>{PRIVATE_SERVER_NAME}</strong>
                  <p>Pembayaran sudah diverifikasi admin. Silakan masuk melalui link private server.</p>
                </>
              ) : (
                <p>Private server akan tersedia setelah pembayaran dikonfirmasi oleh admin.</p>
              )}
            </div>
            {canOpenPrivateServer && (
              <Button
                as="a"
                href={PRIVATE_SERVER_URL}
                target="_blank"
                rel="noreferrer"
                variant="secondary"
              >
                Buka server
              </Button>
            )}
          </section>
        )}

        {transaction && (
          <article className="transaction-result">
            <div className="transaction-result__header">
              <div>
                <span>Kode</span>
                <strong>{transaction.code}</strong>
                <Button variant="secondary" size="sm" onClick={handleCopyCode}>
                  {copied ? 'Copied' : 'Copy'}
                </Button>
              </div>
            </div>

            {transaction.statusNote && (
              <div className="status-note" data-status={transaction.rawStatus}>
                <span>Catatan status</span>
                <p>{transaction.statusNote}</p>
              </div>
            )}

            <div className="transaction-meta">
              <span>Dibayar</span>
              <strong>{formatDate(transaction.paidAt)}</strong>
              <span>Username Roblox</span>
              <strong>{transaction.buyer.robloxUsername}</strong>
            </div>

            {transaction.deliveryProof?.url && (
              <div className="transaction-proof">
                <span>Bukti trade</span>
                <img
                  src={transaction.deliveryProof.url}
                  alt={`Bukti trade order ${transaction.code}`}
                />
              </div>
            )}

            <div className="transaction-items">
              {transaction.items.map((item) => (
                <div key={item.cartKey || item.id}>
                  <span>
                    {item.type === 'token'
                      ? item.name
                      : `${item.name} x${item.quantity}`}
                    <small>
                      {item.type === 'token'
                        ? `${item.packageLabel} - ${formatPrice(item.price)}`
                        : `${item.mutation} - ${formatWeight(item.weightKg)}`}
                    </small>
                  </span>
                  <strong>{formatPrice(item.price * item.quantity)}</strong>
                </div>
              ))}
            </div>
            <div className="transaction-total">
              <span>Total</span>
              <strong>{formatPrice(transaction.summary.total)}</strong>
            </div>
          </article>
        )}

        {hasSearched && !transaction && (
          <section className="transaction-alert">
            <Alert tone="warning" title="Kode tidak ditemukan">
              Pastikan kode sama seperti yang muncul setelah payment.
            </Alert>
            <Button as={Link} to="/" variant="ghost">
              Kembali ke market
            </Button>
          </section>
        )}
      </section>
    </div>
  )
}

export default CheckTransaction

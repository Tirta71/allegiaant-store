import { useState } from 'react'
import { Link } from 'react-router-dom'
import Alert from '../components/ui/Alert'
import Button from '../components/ui/Button'
import { useAlert } from '../context/useAlert'
import { formatPrice, formatWeight } from '../data/pets'
import { fetchOrder } from '../services/api'

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
  const [code, setCode] = useState('')
  const [transaction, setTransaction] = useState(null)
  const [hasSearched, setHasSearched] = useState(false)
  const [copied, setCopied] = useState(false)
  const [checkedCode, setCheckedCode] = useState('')
  const [isSearching, setIsSearching] = useState(false)
  const sellerWhatsappUrl = getSellerWhatsappUrl(transaction?.code || checkedCode)
  const canOpenPrivateServer = transaction
    ? PRIVATE_SERVER_VISIBLE_STATUSES.has(transaction.rawStatus)
    : false

  async function handleSubmit(event) {
    event.preventDefault()
    const nextCheckedCode = code.trim().toUpperCase()

    setIsSearching(true)

    try {
      const foundTransaction = await fetchOrder(nextCheckedCode)

      setTransaction(foundTransaction)
      setHasSearched(true)
      setCopied(false)
      setCheckedCode(nextCheckedCode)
      showAlert({
        tone: 'success',
        title: 'Transaksi ditemukan',
        message: `${foundTransaction.code} sedang diproses.`,
      })
    } catch (requestError) {
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
    } finally {
      setIsSearching(false)
    }
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
              
              <div>
                <span>Status</span>
                <strong>{transaction.status}</strong>
              </div>
            </div>

            <div className="transaction-meta">
              <span>Dibayar</span>
              <strong>{formatDate(transaction.paidAt)}</strong>
              <span>Username Roblox</span>
              <strong>{transaction.buyer.robloxUsername}</strong>
            </div>

                <div className="private-server-card">
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
                      size="sm"
                    >
                      Buka server
                    </Button>
                  )}
                </div>
              {transaction.statusNote && (
                <div className="status-note" data-status={transaction.rawStatus}>
                  <span>Catatan status</span>
                  <p>{transaction.statusNote}</p>
              </div>
            )}

          

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

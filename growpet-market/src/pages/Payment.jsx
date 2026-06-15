import { useState } from 'react'
import { Link } from 'react-router-dom'
import Button from '../components/ui/Button'
import SectionHeader from '../components/ui/SectionHeader'
import { useAlert } from '../context/useAlert'
import { useCart } from '../context/useCart'
import { formatPrice, formatWeight } from '../data/pets'
import {
  clearPendingOrder,
  createTransaction,
  getPendingOrder,
} from '../utils/transactions'

function Payment() {
  const { clearCart } = useCart()
  const { showAlert } = useAlert()
  const [pendingOrder, setPendingOrder] = useState(() => getPendingOrder())
  const [transaction, setTransaction] = useState(null)
  const [copied, setCopied] = useState(false)

  function handleConfirmPayment() {
    const nextTransaction = createTransaction(pendingOrder)
    clearPendingOrder()
    clearCart()
    setTransaction(nextTransaction)
    setPendingOrder(null)
    showAlert({
      tone: 'success',
      title: 'Payment dikonfirmasi',
      message: `Kode ${nextTransaction.code} sudah dibuat.`,
    })
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

  const order = transaction || pendingOrder

  return (
    <div className="container page-flow">
      <SectionHeader
        eyebrow="Payment"
        title={transaction ? 'Payment berhasil dikonfirmasi' : 'Selesaikan payment'}
        description="Ini simulasi payment frontend-only. Setelah konfirmasi, kode transaksi akan dibuat."
      />

      <section className="payment-layout">
        <div className="payment-card">
          {transaction ? (
            <>
              <p className="payment-label">Kode transaksi</p>
              <div className="transaction-code-row">
                <div className="transaction-code">{transaction.code}</div>
                <Button variant="secondary" onClick={handleCopyCode}>
                  {copied ? 'Copied' : 'Copy'}
                </Button>
              </div>
              <p className="payment-note">
                Simpan kode ini untuk cek status transaksi di halaman cek
                transaksi.
              </p>
              <div className="payment-actions">
                <Button as={Link} to="/cek-transaksi">
                  Cek transaksi
                </Button>
                <Button as={Link} to="/" variant="ghost">
                  Kembali ke market
                </Button>
              </div>
            </>
          ) : (
            <>
              <div className="payment-method">
                <span>QRIS / E-wallet</span>
                <strong>{formatPrice(order.summary.total)}</strong>
              </div>
              <div className="fake-qr" aria-label="Payment QR preview">
                <span>GPM</span>
              </div>
              <p className="payment-note">
                Transfer sesuai total, lalu klik konfirmasi untuk membuat kode
                transaksi.
              </p>
              <Button onClick={handleConfirmPayment}>Saya sudah bayar</Button>
            </>
          )}
        </div>

        <aside className="payment-summary">
          <h2>Ringkasan order</h2>
          <div className="payment-buyer">
            <span>Roblox</span>
            <strong>{order.buyer.robloxUsername}</strong>
            <span>WhatsApp</span>
            <strong>{order.buyer.whatsapp}</strong>
          </div>
          <div className="payment-items">
            {order.items.map((item) => (
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
          <div className="payment-total">
            <span>Total</span>
            <strong>{formatPrice(order.summary.total)}</strong>
          </div>
        </aside>
      </section>
    </div>
  )
}

export default Payment

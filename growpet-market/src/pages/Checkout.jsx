import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import OrderSummary from '../components/cart/OrderSummary'
import Button from '../components/ui/Button'
import SectionHeader from '../components/ui/SectionHeader'
import { useAlert } from '../context/useAlert'
import { useCart } from '../context/useCart'
import { cancelOrder, createOrder, fetchOrder } from '../services/api'
import {
  clearPendingOrder,
  getPendingOrder,
  savePendingOrder,
} from '../utils/transactions'

function createOrderPayload(form, items) {
  return {
    buyer: {
      roblox_username: form.robloxUsername.trim(),
      whatsapp: form.whatsapp.trim(),
      notes: form.notes.trim() || null,
    },
    items: items.map((item) =>
      item.type === 'token'
        ? {
            type: 'token',
            product_id: item.productId,
            token_rate_id: item.tokenRateId,
            nominal: item.price,
          }
        : {
            type: 'pet',
            product_variant_id: item.productVariantId,
            quantity: item.quantity,
          },
    ),
  }
}

function Checkout() {
  const navigate = useNavigate()
  const { items } = useCart()
  const { showAlert } = useAlert()
  const [activePendingOrder, setActivePendingOrder] = useState(null)
  const [isCheckingPending, setIsCheckingPending] = useState(true)
  const [isCancellingPending, setIsCancellingPending] = useState(false)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [form, setForm] = useState({
    robloxUsername: '',
    whatsapp: '',
    notes: '',
  })

  function updateField(event) {
    const { name, value } = event.target
    setForm((currentForm) => ({ ...currentForm, [name]: value }))
  }

  useEffect(() => {
    const pendingOrder = getPendingOrder()

    if (!pendingOrder?.code) {
      setIsCheckingPending(false)
      return undefined
    }

    let isActive = true

    fetchOrder(pendingOrder.code)
      .then((freshOrder) => {
        if (!isActive) {
          return
        }

        if (freshOrder.rawStatus === 'pending_payment') {
          setActivePendingOrder(freshOrder)
          savePendingOrder(freshOrder)
          return
        }

        clearPendingOrder()
      })
      .catch(() => {
        if (isActive) {
          clearPendingOrder()
        }
      })
      .finally(() => {
        if (isActive) {
          setIsCheckingPending(false)
        }
      })

    return () => {
      isActive = false
    }
  }, [])

  async function handleCancelPendingOrder() {
    if (!activePendingOrder) {
      return
    }

    setIsCancellingPending(true)

    try {
      await cancelOrder(activePendingOrder.code, 'Pesanan dibatalkan buyer dari halaman checkout.')
      clearPendingOrder()
      setActivePendingOrder(null)
      showAlert({
        tone: 'success',
        title: 'Pesanan dibatalkan',
        message: 'Stok sudah dikembalikan. Kamu bisa checkout lagi.',
      })
    } catch (requestError) {
      showAlert({
        tone: 'error',
        title: 'Gagal membatalkan',
        message: requestError.message,
      })
    } finally {
      setIsCancellingPending(false)
    }
  }

  async function handleSubmit(event) {
    event.preventDefault()

    if (activePendingOrder) {
      showAlert({
        tone: 'warning',
        title: 'Masih ada pesanan pending',
        message: 'Selesaikan atau batalkan pesanan sebelumnya dulu.',
      })
      navigate('/payment')
      return
    }

    if (items.some((item) => item.type === 'pet' && !item.productVariantId)) {
      showAlert({
        tone: 'error',
        title: 'Data varian belum lengkap',
        message: 'Hapus item lama dari cart, lalu pilih pet lagi dari katalog API.',
      })
      return
    }

    if (
      items.some(
        (item) => item.type === 'token' && (!item.productId || !item.tokenRateId),
      )
    ) {
      showAlert({
        tone: 'error',
        title: 'Data token belum lengkap',
        message: 'Hapus item token lama dari cart, lalu pilih token lagi dari katalog API.',
      })
      return
    }

    setIsSubmitting(true)

    try {
      const order = await createOrder(createOrderPayload(form, items))
      savePendingOrder(order)
      showAlert({
        tone: 'info',
        title: 'Order dibuat',
        message: `Kode ${order.code} sudah dibuat. Lanjutkan payment.`,
      })
      navigate('/payment')
    } catch (requestError) {
      showAlert({
        tone: 'error',
        title: 'Checkout gagal',
        message: requestError.message,
      })
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="container page-flow">
      <SectionHeader
        eyebrow="Checkout"
        title="Lengkapi data order"
        description="Order akan dibuat langsung ke backend agar masuk dashboard admin."
      />

      {isCheckingPending ? (
        <section className="empty-state">
          <h1>Mengecek pesanan pending</h1>
          <p>Sebentar, sistem memastikan tidak ada payment yang masih berjalan.</p>
        </section>
      ) : activePendingOrder ? (
        <section className="empty-state">
          <h1>Masih ada pesanan pending</h1>
          <p>
            Kode {activePendingOrder.code} masih menunggu payment. Selesaikan atau
            batalkan dulu sebelum checkout lagi.
          </p>
          <div className="payment-actions">
            <Button as={Link} to="/payment">
              Lanjut payment
            </Button>
            {activePendingOrder.canCancel && (
              <Button
                variant="secondary"
                onClick={handleCancelPendingOrder}
                disabled={isCancellingPending}
              >
                {isCancellingPending ? 'Membatalkan...' : 'Batalkan pesanan'}
              </Button>
            )}
          </div>
        </section>
      ) : items.length === 0 ? (
        <section className="empty-state">
          <h1>Belum ada item untuk checkout</h1>
          <p>Tambahkan pet dulu dari market.</p>
          <Button as={Link} to="/">
            Kembali ke market
          </Button>
        </section>
      ) : (
        <section className="checkout-layout">
          <form className="checkout-form" onSubmit={handleSubmit}>
            <label>
              <span>Roblox username</span>
              <input
                type="text"
                name="robloxUsername"
                value={form.robloxUsername}
                onChange={updateField}
                placeholder="contoh: GardenBuyer01"
                required
              />
            </label>
            <label>
              <span>WhatsApp</span>
              <input
                type="tel"
                name="whatsapp"
                value={form.whatsapp}
                onChange={updateField}
                placeholder="08xxxxxxxxxx"
                required
              />
            </label>
            <label>
              <span>Notes</span>
              <textarea
                name="notes"
                value={form.notes}
                onChange={updateField}
                placeholder="Catatan delivery atau request tambahan"
                rows="5"
              />
            </label>

            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? 'Membuat order...' : 'Lanjut ke payment'}
            </Button>
          </form>

          <div className="checkout-side">
            <OrderSummary showCheckout={false} />
          </div>
        </section>
      )}
    </div>
  )
}

export default Checkout

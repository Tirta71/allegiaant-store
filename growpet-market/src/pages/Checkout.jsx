import { useEffect, useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import OrderSummary from '../components/cart/OrderSummary'
import Button from '../components/ui/Button'
import PageHeader from '../components/ui/PageHeader'
import { useAlert } from '../context/useAlert'
import { useCart } from '../context/useCart'
import { createOrder, fetchOrder } from '../features/orders/orders.api'
import {
  clearPendingOrder,
  getCheckTransactionPath,
  getPendingOrder,
  isOrderCheckoutBlocked,
  savePendingOrder,
  shouldContinuePayment,
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
  const [isCheckingPending, setIsCheckingPending] = useState(() =>
    Boolean(getPendingOrder()?.code),
  )
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
      return undefined
    }

    let isActive = true

    fetchOrder(pendingOrder.code)
      .then((freshOrder) => {
        if (!isActive) {
          return
        }

        if (isOrderCheckoutBlocked(freshOrder)) {
          savePendingOrder(freshOrder)
          const shouldPay = shouldContinuePayment(freshOrder)

          showAlert({
            tone: 'warning',
            title: shouldPay ? 'Payment masih tertunda' : 'Pesanan belum selesai',
            message: shouldPay
              ? 'Selesaikan atau batalkan payment sebelumnya dulu.'
              : 'Kamu bisa checkout lagi setelah order sebelumnya delivered.',
          })
          navigate(shouldPay ? '/payment' : getCheckTransactionPath(freshOrder), {
            replace: true,
          })
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
  }, [navigate, showAlert])

  async function handleSubmit(event) {
    event.preventDefault()

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
      <PageHeader
        eyebrow="Checkout"
        title="Lengkapi data order"
        description="Order akan dibuat langsung ke backend agar masuk dashboard admin."
      />

      {isCheckingPending ? (
        <section className="empty-state">
          <h1>Mengecek pesanan pending</h1>
          <p>Sebentar, sistem memastikan tidak ada payment yang masih berjalan.</p>
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

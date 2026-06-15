import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import OrderSummary from '../components/cart/OrderSummary'
import Button from '../components/ui/Button'
import SectionHeader from '../components/ui/SectionHeader'
import { useAlert } from '../context/useAlert'
import { useCart } from '../context/useCart'
import { savePendingOrder } from '../utils/transactions'

function Checkout() {
  const navigate = useNavigate()
  const { items, subtotal, total, totalItems } = useCart()
  const { showAlert } = useAlert()
  const [form, setForm] = useState({
    robloxUsername: '',
    whatsapp: '',
    notes: '',
  })

  function updateField(event) {
    const { name, value } = event.target
    setForm((currentForm) => ({ ...currentForm, [name]: value }))
  }

  function handleSubmit(event) {
    event.preventDefault()
    savePendingOrder({
      buyer: form,
      items,
      summary: {
        subtotal,
        total,
        totalItems,
      },
      createdAt: new Date().toISOString(),
    })
    showAlert({
      tone: 'info',
      title: 'Data order tersimpan',
      message: 'Lanjutkan payment untuk membuat kode transaksi.',
    })
    navigate('/payment')
  }

  return (
    <div className="container page-flow">
      <SectionHeader
        eyebrow="Checkout"
        title="Lengkapi data order"
        description="Form ini hanya simulasi frontend, tanpa backend dan tanpa API."
      />

      {items.length === 0 ? (
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

            <Button type="submit">Lanjut ke payment</Button>
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

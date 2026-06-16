import { useNavigate } from 'react-router-dom'
import { formatPrice, formatWeight } from '../../data/pets'
import { useCart } from '../../context/useCart'
import { usePendingPaymentGuard } from '../../hooks/usePendingPaymentGuard'
import Button from '../ui/Button'

function OrderSummary({
  items: orderItems,
  showCheckout = true,
  summary: orderSummary,
  title = 'Ringkasan pesanan',
}) {
  const navigate = useNavigate()
  const cart = useCart()
  const guardPendingPayment = usePendingPaymentGuard()
  const items = orderItems || cart.items
  const subtotal = orderSummary?.subtotal ?? cart.subtotal
  const total = orderSummary?.total ?? cart.total
  const totalItems = orderSummary?.totalItems ?? cart.totalItems

  function getItemDetail(item) {
    if (item.type === 'token') {
      return `Nominal ${formatPrice(item.price)} - ${item.packageLabel}`
    }

    return `${item.mutation} - ${formatWeight(item.weightKg)} - Qty ${
      item.quantity
    }`
  }

  async function handleCheckoutClick() {
    if (await guardPendingPayment()) {
      return
    }

    navigate('/checkout')
  }

  return (
    <aside className="order-summary">
      <h2>{title}</h2>
      {items.length > 0 && (
        <div className="summary-items">
          {items.map((item) => (
            <div className="summary-item" key={item.cartKey || item.id}>
              <div className="summary-item__title">
                <strong>{item.name}</strong>
              </div>
              <div className="summary-item__detail">
                <span>{getItemDetail(item)}</span>
                <strong className="summary-item__price">
                  {formatPrice(item.lineTotal ?? item.price * item.quantity)}
                </strong>
              </div>
            </div>
          ))}
        </div>
      )}

      <div className="summary-lines">
        <div>
          <span>Items</span>
          <strong>{totalItems}</strong>
        </div>
        <div>
          <span>Subtotal</span>
          <strong>{formatPrice(subtotal)}</strong>
        </div>
        <div className="summary-total">
          <span>Total</span>
          <strong>{formatPrice(total)}</strong>
        </div>
      </div>

      {showCheckout && items.length > 0 && (
        <Button onClick={handleCheckoutClick} className="summary-button">
          Lanjut checkout
        </Button>
      )}
    </aside>
  )
}

export default OrderSummary

import { Link } from 'react-router-dom'
import { formatPrice, formatWeight } from '../../data/pets'
import { useCart } from '../../context/useCart'
import Button from '../ui/Button'

function OrderSummary({ showCheckout = true }) {
  const { items, subtotal, total, totalItems } = useCart()

  function getItemDetail(item) {
    if (item.type === 'token') {
      return `Nominal ${formatPrice(item.price)} - ${item.packageLabel}`
    }

    return `${item.mutation} - ${formatWeight(item.weightKg)} - Qty ${
      item.quantity
    }`
  }

  return (
    <aside className="order-summary">
      <h2>Order summary</h2>
      {items.length > 0 && (
        <div className="summary-items">
          {items.map((item) => (
            <div className="summary-item" key={item.cartKey}>
              <div className="summary-item__title">
                <strong>{item.name}</strong>
              </div>
              <div className="summary-item__detail">
                <span>{getItemDetail(item)}</span>
                <strong className="summary-item__price">
                  {formatPrice(item.price * item.quantity)}
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
        <Button as={Link} to="/checkout" className="summary-button">
          Lanjut checkout
        </Button>
      )}
    </aside>
  )
}

export default OrderSummary

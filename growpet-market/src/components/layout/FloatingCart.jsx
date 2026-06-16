import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useCart } from '../../context/useCart'
import { usePendingPaymentGuard } from '../../hooks/usePendingPaymentGuard'

function FloatingCart() {
  const { totalItems } = useCart()
  const navigate = useNavigate()
  const { pathname } = useLocation()
  const guardPendingPayment = usePendingPaymentGuard()

  if (pathname === '/cart') {
    return null
  }

  async function handleClick(event) {
    event.preventDefault()

    if (await guardPendingPayment()) {
      return
    }

    navigate('/cart')
  }

  return (
    <Link to="/cart" className="floating-cart" aria-label="Buka keranjang" onClick={handleClick}>
      <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M7.2 8h13.1l-1.4 7.2a2 2 0 0 1-2 1.6H9.5a2 2 0 0 1-2-1.7L6.2 5.8H3.8" />
        <circle cx="9.8" cy="20" r="1.2" />
        <circle cx="17.3" cy="20" r="1.2" />
      </svg>
      {totalItems > 0 && <span>{totalItems}</span>}
    </Link>
  )
}

export default FloatingCart

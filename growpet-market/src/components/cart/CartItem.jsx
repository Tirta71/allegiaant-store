import { Link } from 'react-router-dom'
import { formatPrice, formatWeight } from '../../data/pets'
import { useCart } from '../../context/useCart'
import Badge from '../ui/Badge'

function CartItem({ item }) {
  const { removeFromCart, updateQuantity, updateTokenPrice } = useCart()
  const isToken = item.type === 'token'
  const detailLink = isToken ? '/' : `/pet/${item.id}`
  const badgeTone = isToken ? 'mint' : item.rarity.toLowerCase()
  const badgeLabel = isToken ? item.category : item.rarity
  const totalLabel = isToken ? 'Token didapat' : 'Total item'
  const totalValue = isToken ? item.packageLabel : formatPrice(item.price * item.quantity)

  function handleTokenPriceChange(event) {
    const digitsOnly = event.target.value.replace(/\D/g, '')
    updateTokenPrice(item.cartKey, digitsOnly ? Number(digitsOnly) : 0)
  }

  return (
    <article className={`cart-item ${isToken ? 'cart-item--token' : 'cart-item--pet'}`}>
      <div
        className="cart-item__art pet-art"
        role="img"
        aria-label={`${item.name} pet illustration`}
        style={{ '--pet-accent': item.accent, '--pet-soft': item.soft }}
      >
        <span>{item.name.slice(0, 1)}</span>
      </div>

      <div className="cart-item__info">
        <Badge tone={badgeTone}>{badgeLabel}</Badge>
        <Link to={detailLink}>{item.name}</Link>
        <p>{formatPrice(item.price)}</p>
        <div className="cart-item__meta">
          {isToken ? (
            <>
              <span>{item.packageLabel}</span>
              <span>Instant</span>
            </>
          ) : (
            <>
              <span>{item.mutation}</span>
              <span>{formatWeight(item.weightKg)}</span>
            </>
          )}
        </div>
      </div>

      <div className="cart-item__controls">
        <div className="cart-item__total">
          <span>{totalLabel}</span>
          <strong>{totalValue}</strong>
        </div>
        <div
          className={`cart-item__actions ${
            isToken ? 'cart-item__actions--token' : ''
          }`}
        >
          {isToken ? (
            <label className="token-cart-input">
              <span>Nominal</span>
              <input
                type="text"
                inputMode="numeric"
                value={item.price ? formatPrice(item.price) : ''}
                onChange={handleTokenPriceChange}
                placeholder="Rp 16.500"
              />
            </label>
          ) : (
            <div className="cart-quantity-field">
              <span>Jumlah</span>
              <div
                className="cart-quantity"
                aria-label={`Quantity for ${item.name}`}
              >
                <button
                  type="button"
                  onClick={() =>
                    updateQuantity(item.cartKey, item.quantity - 1)
                  }
                  disabled={item.quantity <= 1}
                >
                  -
                </button>
                <span>{item.quantity}</span>
                <button
                  type="button"
                  onClick={() =>
                    updateQuantity(item.cartKey, item.quantity + 1)
                  }
                  disabled={item.quantity >= item.stock}
                >
                  +
                </button>
              </div>
            </div>
          )}
          <button
            type="button"
            className="remove-button"
            onClick={() => removeFromCart(item.cartKey)}
          >
            Hapus
          </button>
        </div>
      </div>
    </article>
  )
}

export default CartItem

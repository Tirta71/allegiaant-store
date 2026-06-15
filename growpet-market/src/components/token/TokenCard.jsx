import { useState } from 'react'
import { formatPrice } from '../../data/pets'
import {
  calculateTokenAmount,
  formatTokenAmount,
} from '../../data/tokens'
import { useAlert } from '../../context/useAlert'
import { useCart } from '../../context/useCart'
import Button from '../ui/Button'

function TokenCard({ product }) {
  const { addToCart } = useCart()
  const { showAlert } = useAlert()
  const [tokenPrice, setTokenPrice] = useState(product.pricePerThousand)
  const tokenAmount = calculateTokenAmount(tokenPrice)
  const canAddToken = Number(tokenPrice) > 0 && tokenAmount > 0

  function handlePriceChange(event) {
    const digitsOnly = event.target.value.replace(/\D/g, '')
    setTokenPrice(digitsOnly ? Number(digitsOnly) : '')
  }

  function handleAddToken() {
    if (!canAddToken) {
      return
    }

    addToCart(product, 1, {
      tokenAmount,
      tokenPrice: Number(tokenPrice),
      tokenLabel: `${formatTokenAmount(tokenAmount)} Token`,
    })
    showAlert({
      tone: 'success',
      title: 'Token masuk cart',
      message: `${formatTokenAmount(tokenAmount)} Token siap checkout.`,
    })
  }

  return (
    <article className="token-card">
      <div
        className="token-card__art pet-art"
        role="img"
        aria-label="Token product illustration"
        style={{ '--pet-accent': product.accent, '--pet-soft': product.soft }}
      >
        <span>T</span>
      </div>

      <span className="token-card__rate">
        1K Token = {formatPrice(product.pricePerThousand)}
      </span>

      <div className="token-card__info">
        <h3>{product.name}</h3>
        <p>{formatPrice(product.pricePerThousand)}</p>
        <div className="token-card__meta">
          <span>{formatTokenAmount(calculateTokenAmount(product.pricePerThousand))} Token</span>
          <span>Instant</span>
        </div>
      </div>

      <div className="token-card__controls">
        <div className="token-card__total">
          <span>Token didapat</span>
          <strong>{formatTokenAmount(tokenAmount)} Token</strong>
        </div>

        <div className="token-card__actions">
          <label className="token-card__input">
            <span>Nominal</span>
            <input
              type="text"
              inputMode="numeric"
              value={tokenPrice ? formatPrice(Number(tokenPrice)) : ''}
              onChange={handlePriceChange}
              placeholder="Rp 16.500"
            />
          </label>

          <Button
            className="token-card__buy"
            size="sm"
            variant="soft"
            onClick={handleAddToken}
            disabled={!canAddToken}
          >
            Beli Token
          </Button>
        </div>
      </div>
    </article>
  )
}

export default TokenCard

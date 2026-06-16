import { useId, useState } from 'react'
import { formatPrice } from '../../data/pets'
import {
  calculateTokenAmount,
  formatTokenAmount,
} from '../../data/tokens'
import { useAlert } from '../../context/useAlert'
import { useCart } from '../../context/useCart'
import { usePendingPaymentGuard } from '../../hooks/usePendingPaymentGuard'
import Button from '../ui/Button'
import ProductArt from '../ui/ProductArt'

function TokenCard({ product }) {
  const { addToCart } = useCart()
  const { showAlert } = useAlert()
  const guardPendingPayment = usePendingPaymentGuard()
  const tokenInputId = useId()
  const [tokenPrice, setTokenPrice] = useState(product.pricePerThousand)
  const tokenAmount = calculateTokenAmount(tokenPrice, product.pricePerThousand)
  const minNominal = Number(product.minNominal || 1)
  const stockToken = Number(product.stockToken || 0)
  const normalizedTokenPrice = Number(tokenPrice || 0)
  const canAddToken =
    product.productId &&
    product.tokenRateId &&
    normalizedTokenPrice >= minNominal &&
    tokenAmount <= stockToken &&
    tokenAmount > 0
  const tokenInputHint =
    normalizedTokenPrice > 0 && normalizedTokenPrice < minNominal
      ? `Minimal ${formatPrice(minNominal)}`
      : tokenAmount > stockToken
        ? `Stok Hanya ${formatTokenAmount(stockToken)} Token`
        : ''

  function handlePriceChange(event) {
    const digitsOnly = event.target.value.replace(/\D/g, '')
    setTokenPrice(digitsOnly ? Number(digitsOnly) : '')
  }

  async function handleAddToken() {
    if (await guardPendingPayment()) {
      return
    }

    if (!canAddToken) {
      return
    }

    addToCart(product, 1, {
      tokenAmount,
      tokenPrice: normalizedTokenPrice,
      tokenLabel: `${formatTokenAmount(tokenAmount)} Token`,
      tokenRateId: product.tokenRateId,
      productId: product.productId,
      pricePerThousand: product.pricePerThousand,
    })
    showAlert({
      tone: 'success',
      title: 'Token masuk cart',
      message: `${formatTokenAmount(tokenAmount)} Token siap checkout.`,
    })
  }

  return (
    <article className="token-card">
      <ProductArt className="token-card__art" product={product} />

      <span className="token-card__rate">
        1K Token = {formatPrice(product.pricePerThousand)}
      </span>

      <div className="token-card__info">
        <h3>{product.name}</h3>
        <p>{formatPrice(product.pricePerThousand)}</p>
        <div className="token-card__meta">
          <span>
            {formatTokenAmount(
              calculateTokenAmount(product.pricePerThousand, product.pricePerThousand),
            )}{' '}
            Token
          </span>
          <span>Stok {formatTokenAmount(stockToken)}</span>
        </div>
      </div>

      <div className="token-card__controls">
        <div className="token-card__total">
          <span>Token didapat</span>
          <strong>{formatTokenAmount(tokenAmount)} Token</strong>
        </div>

        <div className="token-card__actions">
          <div className="token-card__input">
            <label htmlFor={tokenInputId}>Nominal</label>
            <input
              id={tokenInputId}
              type="text"
              inputMode="numeric"
              value={tokenPrice ? formatPrice(Number(tokenPrice)) : ''}
              onChange={handlePriceChange}
              placeholder="Rp 15.000"
            />
            <small aria-live="polite">{tokenInputHint || "\u00a0"}</small>
          </div>

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

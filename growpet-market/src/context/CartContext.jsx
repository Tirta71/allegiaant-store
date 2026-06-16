import { useMemo, useState } from 'react'
import { calculateTokenAmount, formatTokenAmount } from '../data/tokens'
import { CartContext as CartStateContext } from './cart-context'

export function CartProvider({ children }) {
  const [items, setItems] = useState([])

  function addToCart(product, quantity = 1, options = {}) {
    if (product.type === 'token') {
      const tokenAmount = Number(options.tokenAmount || 0)
      const tokenPrice = Number(options.tokenPrice || 0)
      const tokenLabel = options.tokenLabel || `${tokenAmount} Token`
      const cartKey = `token-${options.productId || product.productId}-${options.tokenRateId || product.tokenRateId}`
      const stock = product.stock

      setItems((currentItems) => {
        const existingItem = currentItems.find(
          (item) => item.cartKey === cartKey,
        )

        if (existingItem) {
          return currentItems.map((item) =>
            item.cartKey === cartKey
              ? {
                  ...item,
                  packageLabel: tokenLabel,
                  tokenAmount,
                  price: tokenPrice,
                  productId: options.productId || product.productId,
                  tokenRateId: options.tokenRateId || product.tokenRateId,
                  pricePerThousand:
                    options.pricePerThousand || product.pricePerThousand,
                  quantity: 1,
                }
              : item,
          )
        }

        return [
          ...currentItems,
          {
            ...product,
            cartKey,
            packageId: cartKey,
            packageLabel: tokenLabel,
            tokenAmount,
            productId: options.productId || product.productId,
            tokenRateId: options.tokenRateId || product.tokenRateId,
            pricePerThousand: options.pricePerThousand || product.pricePerThousand,
            price: tokenPrice,
            stock,
            quantity: 1,
          },
        ]
      })
      return
    }

    const pet = product
    const mutation = options.mutation || pet.mutations?.[0] || 'Nightmare'
    const weightKg = Number(options.weightKg || pet.weights?.[0] || 1)
    const price = Number(options.price) || 0
    const stock = Number(options.stock ?? pet.stock)
    const productVariantId = options.productVariantId || options.variant?.id
    const cartKey = `pet-${productVariantId || `${pet.id}-${mutation}-${weightKg}`}`

    setItems((currentItems) => {
      const existingItem = currentItems.find((item) => item.cartKey === cartKey)

      if (existingItem) {
        return currentItems.map((item) =>
          item.cartKey === cartKey
              ? {
                  ...item,
                  quantity: Math.min(item.quantity + quantity, stock),
                }
              : item,
        )
      }

      return [
        ...currentItems,
        {
          ...pet,
          cartKey,
          productId: pet.productId,
          productVariantId,
          mutation,
          weightKg,
          price,
          stock,
          quantity: Math.min(quantity, stock),
        },
      ]
    })
  }

  function updateQuantity(cartKey, quantity) {
    setItems((currentItems) =>
      currentItems.map((item) =>
        item.cartKey === cartKey
          ? {
              ...item,
              quantity: Math.max(1, Math.min(Number(quantity), item.stock)),
            }
          : item,
      ),
    )
  }

  function updateTokenPrice(cartKey, tokenPrice) {
    const nextPrice = Math.max(0, Number(tokenPrice) || 0)

    setItems((currentItems) =>
      currentItems.map((item) =>
        item.cartKey === cartKey && item.type === 'token'
          ? (() => {
              const tokenAmount = calculateTokenAmount(
                nextPrice,
                item.pricePerThousand,
              )

              return {
                ...item,
                packageLabel: `${formatTokenAmount(tokenAmount)} Token`,
                tokenAmount,
                price: nextPrice,
                quantity: 1,
              }
            })()
          : item,
      ),
    )
  }

  function removeFromCart(cartKey) {
    setItems((currentItems) =>
      currentItems.filter((item) => item.cartKey !== cartKey),
    )
  }

  function clearCart() {
    setItems([])
  }

  const summary = useMemo(() => {
    const totalItems = items.reduce((total, item) => total + item.quantity, 0)
    const subtotal = items.reduce(
      (total, item) => total + item.price * item.quantity,
      0,
    )

    return {
      totalItems,
      subtotal,
      total: subtotal,
    }
  }, [items])

  const value = {
    items,
    addToCart,
    updateQuantity,
    updateTokenPrice,
    removeFromCart,
    clearCart,
    ...summary,
  }

  return (
    <CartStateContext.Provider value={value}>
      {children}
    </CartStateContext.Provider>
  )
}

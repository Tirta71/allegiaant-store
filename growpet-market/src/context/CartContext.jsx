import { useMemo, useState } from 'react'
import { getPetVariantPrice } from '../data/pets'
import { calculateTokenAmount, formatTokenAmount } from '../data/tokens'
import { CartContext as CartStateContext } from './cart-context'

export function CartProvider({ children }) {
  const [items, setItems] = useState([])

  function addToCart(product, quantity = 1, options = {}) {
    if (product.type === 'token') {
      const tokenAmount = Number(options.tokenAmount || 0)
      const tokenPrice = Number(options.tokenPrice || 0)
      const tokenLabel = options.tokenLabel || `${tokenAmount} Token`
      const cartKey = product.id
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
    const price =
      Number(options.price) || getPetVariantPrice(pet, mutation, weightKg)
    const cartKey = `${pet.id}-${mutation}-${weightKg}`

    setItems((currentItems) => {
      const existingItem = currentItems.find((item) => item.cartKey === cartKey)

      if (existingItem) {
        return currentItems.map((item) =>
          item.cartKey === cartKey
            ? {
                ...item,
                quantity: Math.min(item.quantity + quantity, pet.stock),
              }
            : item,
        )
      }

      return [
        ...currentItems,
        {
          ...pet,
          cartKey,
          mutation,
          weightKg,
          price,
          quantity: Math.min(quantity, pet.stock),
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
    const tokenAmount = calculateTokenAmount(nextPrice)

    setItems((currentItems) =>
      currentItems.map((item) =>
        item.cartKey === cartKey && item.type === 'token'
          ? {
              ...item,
              packageLabel: `${formatTokenAmount(tokenAmount)} Token`,
              tokenAmount,
              price: nextPrice,
              quantity: 1,
            }
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

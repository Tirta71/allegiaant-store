import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { AlertProvider } from '../context/AlertContext'
import { CartContext } from '../context/cart-context'
import { createOrder, fetchOrder } from '../features/orders/orders.api'
import Checkout from './Checkout'

vi.mock('../features/orders/orders.api', () => ({
  createOrder: vi.fn(),
  fetchOrder: vi.fn(),
}))

function renderCheckout() {
  const items = [
    {
      cartKey: 'pet-33',
      type: 'pet',
      name: 'Raccoon',
      mutation: 'Venom',
      weightKg: 60,
      productVariantId: 33,
      price: 15000,
      quantity: 1,
      stock: 7,
    },
    {
      cartKey: 'token-9-11',
      type: 'token',
      name: 'Grow Token',
      productId: 9,
      tokenRateId: 11,
      packageLabel: '1.000 Token',
      price: 5000,
      quantity: 1,
      stock: 100000,
    },
  ]

  return render(
    <MemoryRouter initialEntries={['/checkout']}>
      <AlertProvider>
        <CartContext.Provider
          value={{
            items,
            subtotal: 20000,
            total: 20000,
            totalItems: 2,
            clearCart: vi.fn(),
          }}
        >
          <Routes>
            <Route path="/checkout" element={<Checkout />} />
            <Route path="/payment" element={<div>Payment page</div>} />
          </Routes>
        </CartContext.Provider>
      </AlertProvider>
    </MemoryRouter>,
  )
}

describe('Checkout', () => {
  beforeEach(() => {
    fetchOrder.mockReset()
    createOrder.mockReset()
  })

  it('submits backend-derived order payload without client-side status or role', async () => {
    createOrder.mockResolvedValueOnce({
      code: 'GPM-TEST',
      rawStatus: 'pending_payment',
      paymentExpiresAt: new Date(Date.now() + 600000).toISOString(),
      summary: { total: 20000, subtotal: 20000, totalItems: 2 },
      items: [],
      payments: [],
    })

    const user = userEvent.setup()
    renderCheckout()

    await user.type(screen.getByLabelText(/Roblox username/i), 'tasta')
    await user.type(screen.getByLabelText(/WhatsApp/i), '08123456789')
    await user.click(screen.getByRole('button', { name: 'Lanjut ke payment' }))

    await waitFor(() => {
      expect(createOrder).toHaveBeenCalledTimes(1)
    })

    expect(createOrder).toHaveBeenCalledWith({
      buyer: {
        roblox_username: 'tasta',
        whatsapp: '08123456789',
        notes: null,
      },
      items: [
        {
          type: 'pet',
          product_variant_id: 33,
          quantity: 1,
        },
        {
          type: 'token',
          product_id: 9,
          token_rate_id: 11,
          nominal: 5000,
        },
      ],
    })
    expect(JSON.stringify(createOrder.mock.calls[0][0])).not.toMatch(/status|role/i)
    expect(await screen.findByText('Payment page')).toBeInTheDocument()
  })
})

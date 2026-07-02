import { act, render, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { AlertProvider } from '../context/AlertContext'
import { CartProvider } from '../context/CartContext'
import { fetchOrder } from '../features/orders/orders.api'
import { savePendingOrder } from '../utils/transactions'
import Payment from './Payment'

vi.mock('qrcode', () => ({
  default: {
    toDataURL: vi.fn(async () => 'data:image/png;base64,pakasir-qris'),
  },
}))

vi.mock('../features/orders/orders.api', () => ({
  cancelOrder: vi.fn(),
  fetchOrder: vi.fn(),
}))

const pendingOrder = {
  code: 'GPM-TEST',
  rawStatus: 'pending_payment',
  status: 'Menunggu payment',
  statusNote: 'Order dibuat. Stok dikunci selama 10 menit menunggu payment Pakasir.',
  paymentExpiresAt: new Date(Date.now() + 600000).toISOString(),
  summary: { total: 15000, subtotal: 15000, totalItems: 1 },
  paymentInstructions: {
    method: 'pakasir',
    merchantName: 'ALLEGIAANT PET SHOP',
    amount: 16003,
    orderAmount: 15000,
    fee: 1003,
    totalPayment: 16003,
    qrisPayload: 'pakasir-qris-payload',
    qrisOnly: true,
  },
  items: [
    {
      id: 10,
      cartKey: 'order-10',
      type: 'pet',
      name: 'Test Pet',
      mutation: 'Venom',
      weightKg: 60,
      price: 15000,
      quantity: 1,
      lineTotal: 15000,
    },
  ],
  payments: [],
}

function renderPayment() {
  return render(
    <MemoryRouter initialEntries={['/payment']}>
      <AlertProvider>
        <CartProvider>
          <Routes>
            <Route path="/payment" element={<Payment />} />
            <Route
              path="/cek-transaksi"
              element={<div>Check transaction page</div>}
            />
          </Routes>
        </CartProvider>
      </AlertProvider>
    </MemoryRouter>,
  )
}

describe('Payment', () => {
  beforeEach(() => {
    window.localStorage.clear()
    vi.useRealTimers()
    fetchOrder.mockReset()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('shows QRIS for a pending order', async () => {
    fetchOrder.mockResolvedValueOnce(pendingOrder)
    savePendingOrder(pendingOrder)

    renderPayment()

    expect(await screen.findByText('QRIS')).toBeInTheDocument()
    expect(screen.getByText('GPM-TEST')).toBeInTheDocument()
    expect(await screen.findByAltText('QRIS payment')).toHaveAttribute(
      'src',
      'data:image/png;base64,pakasir-qris',
    )
    expect(screen.getByText(/fee payment/i)).toBeInTheDocument()
  })

  it('automatically redirects when payment is confirmed by polling', async () => {
    vi.useFakeTimers()
    fetchOrder
      .mockResolvedValueOnce(pendingOrder)
      .mockResolvedValueOnce({
        ...pendingOrder,
        rawStatus: 'payment_confirmed',
        status: 'Payment confirmed',
        statusNote: 'Payment Pakasir completed.',
        paymentExpiresAt: null,
        paidAt: new Date().toISOString(),
        payments: [{ id: 99, status: 'confirmed' }],
      })
    savePendingOrder(pendingOrder)

    renderPayment()

    expect(screen.getByLabelText('QRIS payment')).toBeInTheDocument()

    await act(async () => {
      await Promise.resolve()
    })

    await act(async () => {
      await vi.advanceTimersByTimeAsync(5000)
    })

    expect(fetchOrder).toHaveBeenCalledTimes(2)
    expect(screen.getByText('Check transaction page')).toBeInTheDocument()
  })
})

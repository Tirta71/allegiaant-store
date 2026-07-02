import { render, screen } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { AlertProvider } from '../../context/AlertContext'
import { fetchOrder } from '../../features/orders/orders.api'
import { savePendingOrder } from '../../utils/transactions'
import PendingPaymentGate from './PendingPaymentGate'

vi.mock('../../features/orders/orders.api', () => ({
  fetchOrder: vi.fn(),
}))

function renderGate() {
  return render(
    <MemoryRouter initialEntries={['/']}>
      <AlertProvider>
        <Routes>
          <Route
            path="/"
            element={
              <PendingPaymentGate>
                <div>Home page</div>
              </PendingPaymentGate>
            }
          />
          <Route path="/payment" element={<div>Payment page</div>} />
        </Routes>
      </AlertProvider>
    </MemoryRouter>,
  )
}

describe('PendingPaymentGate', () => {
  beforeEach(() => {
    fetchOrder.mockReset()
  })

  it('redirects to payment when a pending order still needs payment', async () => {
    savePendingOrder({ code: 'GPM-TEST' })
    fetchOrder.mockResolvedValueOnce({
      code: 'GPM-TEST',
      rawStatus: 'pending_payment',
      status: 'Menunggu payment',
      statusNote: 'Menunggu bukti payment.',
      payments: [],
    })

    renderGate()

    expect(await screen.findByText('Payment page')).toBeInTheDocument()
    expect(fetchOrder).toHaveBeenCalledWith('GPM-TEST')
  })
})

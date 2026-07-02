import { screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'

import { fetchOrder } from '../features/orders/orders.api'
import { renderWithApp } from '../test/render'
import CheckTransaction from './CheckTransaction'

vi.mock('../features/orders/orders.api', () => ({
  fetchOrder: vi.fn(),
}))

describe('CheckTransaction', () => {
  it('auto-fills transaction code from URL and renders delivered order details', async () => {
    fetchOrder.mockResolvedValueOnce({
      code: 'GPM-TEST',
      rawStatus: 'delivered',
      status: 'Delivered',
      statusNote: 'Order selesai delivery dari shortcut admin.',
      paidAt: '2026-06-16T10:00:00.000000Z',
      buyer: {
        robloxUsername: 'tasta',
        whatsapp: '08123456789',
      },
      summary: {
        total: 15000,
        subtotal: 15000,
        totalItems: 1,
      },
      deliveryProof: {
        url: '/storage/trade.png',
        uploadedAt: '2026-06-16T10:00:00.000000Z',
      },
      items: [
        {
          id: 10,
          cartKey: 'order-10',
          type: 'pet',
          name: 'Raccoon',
          mutation: 'Venom',
          weightKg: 60,
          price: 15000,
          quantity: 1,
        },
      ],
      payments: [{ id: 99, proofUrl: '/storage/payment.png' }],
    })

    renderWithApp(<CheckTransaction />, {
      route: '/cek-transaksi?code=GPM-TEST',
      withCart: false,
    })

    expect(await screen.findByText('GPM-TEST')).toBeInTheDocument()
    expect(screen.getByText('tasta')).toBeInTheDocument()
    expect(screen.getByText('Order selesai delivery dari shortcut admin.')).toBeInTheDocument()
    expect(screen.getByText('Private server')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: 'Buka server' })).toBeInTheDocument()
    expect(screen.getByText('Raccoon x1')).toBeInTheDocument()
    expect(fetchOrder).toHaveBeenCalledWith('GPM-TEST')
  })
})

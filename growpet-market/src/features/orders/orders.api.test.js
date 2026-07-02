import { beforeEach, describe, expect, it, vi } from 'vitest'

import { API_BASE_URL } from '../../api/client'
import { createOrder, fetchOrder, uploadPaymentProof } from './orders.api'

function mockOrderResponse(data) {
  globalThis.fetch.mockResolvedValueOnce(
    new Response(JSON.stringify({ data }), {
      status: 200,
      headers: { 'Content-Type': 'application/json' },
    }),
  )
}

const backendOrder = {
  id: 1,
  code: 'GPM-TEST',
  status: 'pending_payment',
  status_note: 'Menunggu bukti payment.',
  buyer: {
    roblox_username: 'tasta',
    whatsapp: '08123456789',
    notes: '',
  },
  summary: {
    subtotal: '15000',
    total: '15000',
    total_items: '1',
  },
  payment_instructions: {
    method: 'pakasir',
    amount: '16003',
    order_amount: '15000',
    fee: '1003',
    total_payment: '16003',
    merchant_name: 'ALLEGIAANT PET SHOP',
    qris_payload: 'pakasir-qris-payload',
    expired_at: '2026-06-16T10:15:00.000000Z',
    payment_url: 'https://app.pakasir.com/pay/test-project/15000?order_id=GPM-TEST',
    qris_only: true,
  },
  payments: [],
  items: [
    {
      id: 10,
      type: 'pet',
      product_id: 7,
      product_variant_id: 33,
      product_name: 'Raccoon',
      mutation_name: 'Venom',
      weight_kg: '60',
      unit_price: '15000',
      quantity: '1',
      line_total: '15000',
    },
  ],
}

describe('orders api', () => {
  const apiOrigin = new URL(API_BASE_URL, window.location.origin).origin

  beforeEach(() => {
    globalThis.fetch = vi.fn()
  })

  it('creates an order through backend pricing payload', async () => {
    mockOrderResponse(backendOrder)

    const payload = {
      buyer: { roblox_username: 'tasta', whatsapp: '08123456789' },
      items: [{ product_variant_id: 33, quantity: 1 }],
    }
    const order = await createOrder(payload)
    const [, options] = globalThis.fetch.mock.calls[0]

    expect(options.method).toBe('POST')
    expect(JSON.parse(options.body)).toEqual(payload)
    expect(options.body).not.toContain('status')
    expect(options.body).not.toContain('role')
    expect(order).toMatchObject({
      code: 'GPM-TEST',
      rawStatus: 'pending_payment',
      status: 'Menunggu payment',
      summary: { total: 15000 },
    })
  })

  it('fetches and normalizes order proof URLs and items', async () => {
    mockOrderResponse({
      ...backendOrder,
      status: 'delivered',
      delivery_proof: {
        url: '/storage/trade.png',
        uploaded_at: '2026-06-16T10:00:00.000000Z',
      },
      payments: [{ id: 99, proof_url: '/storage/payment.png' }],
    })

    const order = await fetchOrder('GPM-TEST')

    expect(order.deliveryProof.url).toBe(`${apiOrigin}/storage/trade.png`)
    expect(order.payments[0].proofUrl).toBe(
      `${apiOrigin}/storage/payment.png`,
    )
    expect(order.items[0]).toMatchObject({
      name: 'Raccoon',
      mutation: 'Venom',
      weightKg: 60,
      price: 15000,
    })
  })

  it('uploads payment proof as FormData', async () => {
    mockOrderResponse({
      ...backendOrder,
      payments: [{ id: 99, proof_url: '/storage/payment.png' }],
    })

    const file = new File(['proof'], 'proof.png', { type: 'image/png' })
    await uploadPaymentProof('GPM-TEST', file)

    const [url, options] = globalThis.fetch.mock.calls[0]

    expect(String(url)).toContain('/api/orders/GPM-TEST/payment-proof')
    expect(options.method).toBe('POST')
    expect(options.body).toBeInstanceOf(FormData)
    expect(options.headers['Content-Type']).toBeUndefined()
  })
})

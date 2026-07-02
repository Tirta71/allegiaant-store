import { beforeEach, describe, expect, it, vi } from 'vitest'

import { API_BASE_URL } from '../../api/client'
import { fetchTestimonials } from './testimonials.api'

describe('testimonials api', () => {
  const apiOrigin = new URL(API_BASE_URL, window.location.origin).origin

  beforeEach(() => {
    globalThis.fetch = vi.fn()
  })

  it('normalizes testimonial buyer, item, and trade proof data', async () => {
    globalThis.fetch.mockResolvedValueOnce(
      new Response(
        JSON.stringify({
          data: [
            {
              id: 5,
              roblox_username: 'tasta',
              delivery_proof: {
                url: '/storage/trade.png',
                uploaded_at: '2026-06-16T10:00:00.000000Z',
              },
              items: [
                {
                  id: 10,
                  type: 'pet',
                  name: 'Raccoon',
                  mutation: 'Venom',
                  weight_kg: '60',
                  quantity: '1',
                },
              ],
            },
          ],
        }),
        {
          status: 200,
          headers: { 'Content-Type': 'application/json' },
        },
      ),
    )

    const testimonials = await fetchTestimonials()

    expect(testimonials[0]).toMatchObject({
      robloxUsername: 'tasta',
      deliveryProof: {
        url: `${apiOrigin}/storage/trade.png`,
        uploadedAt: '2026-06-16T10:00:00.000000Z',
      },
    })
    expect(testimonials[0].items[0]).toMatchObject({
      name: 'Raccoon',
      mutation: 'Venom',
      weightKg: 60,
      quantity: 1,
    })
  })
})

import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { fetchTestimonials } from '../../features/testimonials/testimonials.api'
import { renderWithApp } from '../../test/render'
import Testimonials from './Testimonials'

vi.mock('../../features/testimonials/testimonials.api', () => ({
  fetchTestimonials: vi.fn(),
}))

describe('Testimonials', () => {
  beforeEach(() => {
    fetchTestimonials.mockReset()

    Object.defineProperty(window.HTMLElement.prototype, 'scrollIntoView', {
      configurable: true,
      value: vi.fn(),
    })
  })

  it('renders testimonial buyer, purchased item, and opens proof modal', async () => {
    fetchTestimonials.mockResolvedValueOnce([
      {
        id: 5,
        robloxUsername: 'tasta',
        deliveryProof: {
          url: '/storage/trade.png',
          uploadedAt: '2026-06-16T10:00:00.000000Z',
        },
        items: [
          {
            id: 10,
            type: 'pet',
            name: 'Raccoon',
            mutation: 'Venom',
            weightKg: 60,
            quantity: 1,
          },
        ],
      },
    ])

    const user = userEvent.setup()
    renderWithApp(<Testimonials />, { withCart: false })

    expect(await screen.findByText('tasta')).toBeInTheDocument()
    expect(screen.getByText('Raccoon')).toBeInTheDocument()
    expect(screen.getByText('Venom / 60 kg / Qty 1')).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: /Bukti trade tasta/i }))

    expect(
      screen.getByRole('dialog', { name: /Bukti trade tasta/i }),
    ).toBeInTheDocument()
  })

  it('shows carousel controls and can jump to the next testimonial', async () => {
    fetchTestimonials.mockResolvedValueOnce([
      {
        id: 5,
        robloxUsername: 'tasta',
        deliveryProof: {
          url: '/storage/trade.png',
          uploadedAt: '2026-06-16T10:00:00.000000Z',
        },
        items: [
          {
            id: 10,
            type: 'pet',
            name: 'Raccoon',
            mutation: 'Venom',
            weightKg: 60,
            quantity: 1,
          },
        ],
      },
      {
        id: 6,
        robloxUsername: 'allegbuyer',
        deliveryProof: {
          url: '/storage/trade-2.png',
          uploadedAt: '2026-06-17T10:00:00.000000Z',
        },
        items: [
          {
            id: 11,
            type: 'token',
            name: 'Grow Token',
            packageLabel: '10.000 token',
            quantity: 1,
          },
        ],
      },
    ])

    const user = userEvent.setup()
    renderWithApp(<Testimonials />, { withCart: false })

    expect(await screen.findByText('tasta')).toBeInTheDocument()
    expect(screen.getByText('allegbuyer')).toBeInTheDocument()

    await user.click(screen.getByRole('button', { name: 'Testimoni berikutnya' }))

    expect(
      screen.getByRole('button', { name: 'Tampilkan testimoni 2' }),
    ).toHaveAttribute('aria-current', 'true')
    expect(window.HTMLElement.prototype.scrollIntoView).toHaveBeenCalled()
  })
})

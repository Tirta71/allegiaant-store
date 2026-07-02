import { screen } from '@testing-library/react'
import { Route, Routes } from 'react-router-dom'
import { describe, expect, it, vi } from 'vitest'

import { renderWithApp } from '../test/render'
import PetDetail from './PetDetail'

vi.mock('../features/products/products.api', () => ({
  fetchProduct: vi.fn(async () => ({
    id: 'raccoon',
    productId: 7,
    slug: 'raccoon',
    type: 'pet',
    name: 'Raccoon',
    imageUrl: '/storage/raccoon.png',
    rarity: 'Divine',
    description: 'Raccoon ready stock.',
    price: 15000,
    stock: 7,
    sales: 2,
    mutations: ['Venom'],
    weights: [60],
    variants: [
      {
        id: 33,
        productId: 7,
        mutation: 'Venom',
        weightKg: 60,
        price: 15000,
        stock: 7,
      },
    ],
    perks: ['Ready stock'],
  })),
}))

describe('PetDetail', () => {
  it('loads selectable pet variant data from the product API', async () => {
    renderWithApp(
      <Routes>
        <Route path="/pet/:id" element={<PetDetail />} />
      </Routes>,
      { route: '/pet/raccoon' },
    )

    expect(await screen.findByRole('heading', { name: 'Raccoon' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Venom' })).toBeInTheDocument()
    expect(screen.getByText('Rp 15.000')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Beli sekarang' })).toBeEnabled()
  })
})

import { screen } from '@testing-library/react'
import { describe, expect, it, vi } from 'vitest'

import { renderWithApp } from '../test/render'
import Home from './Home'

vi.mock('../features/products/products.api', () => ({
  fetchProducts: vi.fn(async () => [
    {
      id: 'raccoon',
      productId: 7,
      slug: 'raccoon',
      type: 'pet',
      name: 'Raccoon',
      imageUrl: '/storage/raccoon.png',
      rarity: 'Divine',
      category: 'Divine',
      price: 15000,
      stock: 7,
      sales: 2,
      mutations: ['Venom'],
      weights: [60],
      variants: [],
      perks: ['Ready stock'],
      accent: '#fb7185',
      soft: '#ffe4e6',
    },
    {
      id: 'grow-token',
      productId: 9,
      slug: 'grow-token',
      type: 'token',
      name: 'Grow Token',
      imageUrl: '/storage/token.png',
      rarity: 'Token',
      price: 5000,
      stock: 100000,
      sales: 5,
      mutations: [],
      weights: [],
      variants: [],
      tokenRateId: 11,
      pricePerThousand: 5000,
      minNominal: 5000,
      stockToken: 100000,
      accent: '#bce75d',
      soft: '#f7fbd0',
    },
  ]),
}))

describe('Home', () => {
  it('loads token and pet catalog cards from the feature API layer', async () => {
    renderWithApp(<Home />)

    expect(await screen.findByText('Grow Token')).toBeInTheDocument()
    expect(screen.getByText('Raccoon')).toBeInTheDocument()
    expect(screen.getByText('Stok 7')).toBeInTheDocument()
    expect(screen.getByText('Terjual 2')).toBeInTheDocument()
  })
})

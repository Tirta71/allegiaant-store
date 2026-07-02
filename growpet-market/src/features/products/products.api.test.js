import { beforeEach, describe, expect, it, vi } from 'vitest'

import { fetchProduct, fetchProducts } from './products.api'

function mockProductsResponse(data) {
  globalThis.fetch.mockResolvedValueOnce(
    new Response(JSON.stringify({ data }), {
      status: 200,
      headers: { 'Content-Type': 'application/json' },
    }),
  )
}

describe('products api', () => {
  beforeEach(() => {
    globalThis.fetch = vi.fn()
  })

  it('normalizes pet catalog data for cards and detail pages', async () => {
    mockProductsResponse([
      {
        id: 7,
        slug: 'raccoon',
        type: 'pet',
        name: 'Raccoon',
        image_url: '/storage/raccoon.png',
        rarity: 'Divine',
        starting_price: '15000',
        total_stock: '3',
        sales_count: '12',
        featured: 1,
        best_seller: 0,
        mutations: ['Venom'],
        weights: [60],
        variants: [
          {
            id: 33,
            product_id: 7,
            mutation_id: 2,
            mutation: 'Venom',
            weight_kg: '60',
            price: '15000',
            stock: '3',
            sku: 'RAC-VEN-60',
          },
        ],
      },
    ])

    const products = await fetchProducts()

    expect(products[0]).toMatchObject({
      id: 'raccoon',
      productId: 7,
      type: 'pet',
      name: 'Raccoon',
      price: 15000,
      stock: 3,
      sales: 12,
      rarity: 'Divine',
    })
    expect(products[0].variants[0]).toMatchObject({
      id: 33,
      productId: 7,
      price: 15000,
      stock: 3,
    })
  })

  it('normalizes token rate data without requiring manual price entry from API', async () => {
    mockProductsResponse({
      id: 9,
      slug: 'grow-token',
      type: 'token',
      name: 'Grow Token',
      image_url: '/storage/token.png',
      total_stock: '100000',
      sales_count: '5',
      token_rate: {
        id: 11,
        price_per_thousand: '5000',
        min_nominal: '5000',
        stock_token: '100000',
      },
    })

    const product = await fetchProduct('grow-token')

    expect(product).toMatchObject({
      id: 'grow-token',
      type: 'token',
      tokenRateId: 11,
      pricePerThousand: 5000,
      minNominal: 5000,
      stockToken: 100000,
    })
  })
})

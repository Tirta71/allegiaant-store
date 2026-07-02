import { apiRequest } from '../../api/client'

const RARITY_THEME = {
  legendary: { accent: '#facc15', soft: '#fef9c3' },
  mythical: { accent: '#38bdf8', soft: '#e0f2fe' },
  divine: { accent: '#fb7185', soft: '#ffe4e6' },
  prismatic: { accent: '#8b5cf6', soft: '#ede9fe' },
}

function getProductTheme(product) {
  if (product.type === 'token') {
    return { accent: '#bce75d', soft: '#f7fbd0' }
  }

  const rarityKey = String(product.rarity || '').trim().toLowerCase()

  return RARITY_THEME[rarityKey] || { accent: '#a3e635', soft: '#f0f9d7' }
}

function normalizeVariant(variant) {
  return {
    id: variant.id,
    productId: variant.product_id,
    mutationId: variant.mutation_id,
    mutation: variant.mutation,
    weightKg: Number(variant.weight_kg),
    price: Number(variant.price),
    stock: Number(variant.stock),
    sku: variant.sku,
  }
}

export function normalizeProduct(product) {
  const theme = getProductTheme(product)
  const variants = product.variants?.map(normalizeVariant) || []
  const tokenRate = product.token_rate

  return {
    ...theme,
    id: product.slug,
    productId: product.id,
    slug: product.slug,
    type: product.type,
    name: product.name,
    imageUrl: product.image_url,
    rarity: product.rarity || (product.type === 'token' ? 'Token' : 'Pet'),
    category: product.type === 'token' ? 'Token' : product.rarity,
    price: Number(product.starting_price || tokenRate?.price_per_thousand || 0),
    stock: Number(product.total_stock || 0),
    sales: Number(product.sales_count || 0),
    featured: Boolean(product.featured),
    bestSeller: Boolean(product.best_seller),
    description:
      product.type === 'token'
        ? 'Masukkan nominal pembelian, lalu sistem menghitung token yang didapat.'
        : `${product.name} ready stock dengan pilihan mutasi dan berat dari data admin.`,
    perks:
      product.type === 'token'
        ? ['Instant', 'Rate admin', 'Checkout API']
        : ['Ready stock', 'Harga varian', 'Order otomatis'],
    mutations: product.mutations || [],
    weights: product.weights || [],
    variants,
    tokenRate,
    tokenRateId: tokenRate?.id,
    pricePerThousand: Number(tokenRate?.price_per_thousand || 0),
    minNominal: Number(tokenRate?.min_nominal || tokenRate?.price_per_thousand || 1),
    stockToken: Number(tokenRate?.stock_token || 0),
  }
}

export async function fetchProducts(params) {
  const payload = await apiRequest('products', { params })

  return payload.data.map(normalizeProduct)
}

export async function fetchProduct(slug) {
  const payload = await apiRequest(`products/${slug}`)

  return normalizeProduct(payload.data)
}

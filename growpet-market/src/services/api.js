const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'
const API_ORIGIN = new URL(API_BASE_URL).origin

const STATUS_LABELS = {
  pending_payment: 'Menunggu payment',
  payment_confirmed: 'Payment confirmed',
  processing: 'Processing',
  delivered: 'Delivered',
  cancelled: 'Cancelled',
}

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

function endpointUrl(endpoint, params = {}) {
  const url = new URL(`${API_BASE_URL.replace(/\/$/, '')}/${endpoint}`)

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      url.searchParams.set(key, value)
    }
  })

  return url
}

function assetUrl(url) {
  if (!url) {
    return ''
  }

  if (/^(https?:)?\/\//.test(url) || url.startsWith('data:')) {
    return url
  }

  if (url.startsWith('/')) {
    return `${API_ORIGIN}${url}`
  }

  return url
}

async function apiRequest(endpoint, options = {}) {
  const { params, ...requestOptions } = options
  const isFormData = requestOptions.body instanceof FormData
  const response = await fetch(endpointUrl(endpoint, params), {
    headers: {
      Accept: 'application/json',
      ...(requestOptions.body && !isFormData
        ? { 'Content-Type': 'application/json' }
        : {}),
      ...requestOptions.headers,
    },
    ...requestOptions,
  })

  const payload = await response.json().catch(() => null)

  if (!response.ok) {
    const message =
      payload?.message ||
      Object.values(payload?.errors || {})?.flat()?.[0] ||
      'Request API gagal.'
    const error = new Error(message)
    error.status = response.status
    error.payload = payload
    throw error
  }

  return payload
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

function normalizeOrderItem(item) {
  return {
    id: item.id,
    cartKey: `order-${item.id}`,
    type: item.type,
    productId: item.product_id,
    productVariantId: item.product_variant_id,
    tokenRateId: item.token_rate_id,
    name: item.product_name,
    mutation: item.mutation_name,
    weightKg: item.weight_kg ? Number(item.weight_kg) : null,
    tokenAmount: item.token_amount,
    tokenRate: item.token_rate,
    packageLabel: item.package_label,
    price: Number(item.unit_price),
    quantity: Number(item.quantity),
    lineTotal: Number(item.line_total),
  }
}

function normalizeDeliveryProof(order) {
  const proof = order.delivery_proof || order.deliveryProof || {}

  return {
    url: assetUrl(proof.url || order.delivery_proof_url || order.deliveryProofUrl),
    uploadedAt:
      proof.uploaded_at ||
      proof.uploadedAt ||
      order.delivery_proof_uploaded_at ||
      order.deliveryProofUploadedAt ||
      null,
    note: proof.note || order.delivery_proof_note || order.deliveryProofNote || '',
  }
}

export function normalizeOrder(order) {
  return {
    id: order.id,
    code: order.code,
    buyer: {
      robloxUsername: order.buyer?.roblox_username || '',
      whatsapp: order.buyer?.whatsapp || '',
      notes: order.buyer?.notes || '',
    },
    summary: {
      subtotal: Number(order.summary?.subtotal || 0),
      total: Number(order.summary?.total || 0),
      totalItems: Number(order.summary?.total_items || 0),
    },
    rawStatus: order.status,
    status: STATUS_LABELS[order.status] || order.status,
    statusNote: order.status_note,
    deliveryProof: normalizeDeliveryProof(order),
    paidAt: order.paid_at,
    paymentExpiresAt: order.payment_expires_at,
    paymentSecondsRemaining: Number(order.payment_seconds_remaining || 0),
    cancelledAt: order.cancelled_at,
    canCancel: Boolean(order.can_cancel),
    createdAt: order.created_at,
    items: order.items?.map(normalizeOrderItem) || [],
    paymentInstructions: {
      method: order.payment_instructions?.method || 'qris',
      amount: Number(order.payment_instructions?.amount || order.summary?.total || 0),
      merchantName: order.payment_instructions?.merchant_name || '',
      qrisPayload: order.payment_instructions?.qris_payload || '',
      staticImageUrl: assetUrl(order.payment_instructions?.static_image_url),
    },
    payments:
      order.payments?.map((payment) => ({
        ...payment,
        proofUrl: assetUrl(payment.proof_url || payment.proofUrl),
      })) || [],
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

export async function createOrder(payload) {
  const response = await apiRequest('orders', {
    method: 'POST',
    body: JSON.stringify(payload),
  })

  return normalizeOrder(response.data)
}

export async function fetchOrder(code) {
  const response = await apiRequest(`orders/${encodeURIComponent(code)}`)
  return normalizeOrder(response.data)
}

export async function uploadPaymentProof(code, proof) {
  const body = new FormData()
  body.append('proof', proof)

  const response = await apiRequest(`orders/${encodeURIComponent(code)}/payment-proof`, {
    method: 'POST',
    body,
  })

  return normalizeOrder(response.data)
}

export async function cancelOrder(code, reason) {
  const response = await apiRequest(`orders/${encodeURIComponent(code)}/cancel`, {
    method: 'POST',
    body: JSON.stringify({ reason }),
  })

  return normalizeOrder(response.data)
}

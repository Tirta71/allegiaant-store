import { apiRequest, assetUrl } from '../../api/client'

const STATUS_LABELS = {
  pending_payment: 'Menunggu payment',
  payment_confirmed: 'Payment confirmed',
  processing: 'Processing',
  delivered: 'Delivered',
  cancelled: 'Cancelled',
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
      method: order.payment_instructions?.method || 'pakasir',
      amount: Number(order.payment_instructions?.amount || order.summary?.total || 0),
      orderAmount: Number(order.payment_instructions?.order_amount || order.summary?.total || 0),
      fee: Number(order.payment_instructions?.fee || 0),
      totalPayment: Number(
        order.payment_instructions?.total_payment ||
          order.payment_instructions?.amount ||
          order.summary?.total ||
          0,
      ),
      merchantName: order.payment_instructions?.merchant_name || '',
      paymentUrl: order.payment_instructions?.payment_url || '',
      qrisOnly: Boolean(order.payment_instructions?.qris_only),
      qrisPayload: order.payment_instructions?.qris_payload || '',
      expiredAt: order.payment_instructions?.expired_at || null,
      staticImageUrl: assetUrl(order.payment_instructions?.static_image_url),
    },
    payments:
      order.payments?.map((payment) => ({
        ...payment,
        providerPayload: payment.provider_payload || payment.providerPayload || '',
        providerFee: Number(payment.provider_fee || payment.providerFee || 0),
        providerTotal: Number(payment.provider_total || payment.providerTotal || 0),
        providerExpiresAt:
          payment.provider_expires_at || payment.providerExpiresAt || null,
        proofUrl: assetUrl(payment.proof_url || payment.proofUrl),
      })) || [],
  }
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

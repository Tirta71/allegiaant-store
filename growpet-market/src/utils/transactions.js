const PENDING_ORDER_KEY = 'growpet_pending_order'
const FINISHED_ORDER_STATUSES = new Set(['delivered', 'cancelled'])

function readJson(key, fallback) {
  try {
    const value = window.localStorage.getItem(key)
    return value ? JSON.parse(value) : fallback
  } catch {
    return fallback
  }
}

function writeJson(key, value) {
  window.localStorage.setItem(key, JSON.stringify(value))
}

export function savePendingOrder(order) {
  writeJson(PENDING_ORDER_KEY, order)
}

export function getPendingOrder() {
  return readJson(PENDING_ORDER_KEY, null)
}

export function clearPendingOrder() {
  window.localStorage.removeItem(PENDING_ORDER_KEY)
}

export function hasPaymentProof(order) {
  return Boolean(order?.payments?.some((payment) => payment.proofUrl))
}

export function shouldContinuePayment(order) {
  return order?.rawStatus === 'pending_payment' && !hasPaymentProof(order)
}

export function isOrderFinished(order) {
  return FINISHED_ORDER_STATUSES.has(order?.rawStatus)
}

export function isOrderCheckoutBlocked(order) {
  return Boolean(order?.code && order?.rawStatus && !isOrderFinished(order))
}

export function getCheckTransactionPath(orderOrCode) {
  const code =
    typeof orderOrCode === 'string' ? orderOrCode : orderOrCode?.code

  return code
    ? `/cek-transaksi?code=${encodeURIComponent(code)}`
    : '/cek-transaksi'
}

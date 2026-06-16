const PENDING_ORDER_KEY = 'growpet_pending_order'

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

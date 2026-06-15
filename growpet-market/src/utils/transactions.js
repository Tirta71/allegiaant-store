const PENDING_ORDER_KEY = 'growpet_pending_order'
const TRANSACTIONS_KEY = 'growpet_transactions'

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

function createTransactionCode() {
  const date = new Date()
  const datePart = [
    date.getFullYear(),
    String(date.getMonth() + 1).padStart(2, '0'),
    String(date.getDate()).padStart(2, '0'),
  ].join('')
  const randomPart = Math.random().toString(36).slice(2, 7).toUpperCase()

  return `GPM-${datePart}-${randomPart}`
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

export function getTransactions() {
  return readJson(TRANSACTIONS_KEY, [])
}

export function createTransaction(order) {
  const transaction = {
    ...order,
    code: createTransactionCode(),
    status: 'Payment confirmed',
    statusNote: 'Order masuk antrean delivery pet.',
    paidAt: new Date().toISOString(),
  }
  const transactions = getTransactions()

  writeJson(TRANSACTIONS_KEY, [transaction, ...transactions])
  return transaction
}

export function findTransaction(code) {
  const normalizedCode = code.trim().toUpperCase()
  return getTransactions().find(
    (transaction) => transaction.code.toUpperCase() === normalizedCode,
  )
}

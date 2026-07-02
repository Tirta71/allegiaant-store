import { useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAlert } from '../context/useAlert'
import { fetchOrder } from '../features/orders/orders.api'
import {
  clearPendingOrder,
  getCheckTransactionPath,
  getPendingOrder,
  isOrderCheckoutBlocked,
  savePendingOrder,
  shouldContinuePayment,
} from '../utils/transactions'

export function usePendingPaymentGuard() {
  const navigate = useNavigate()
  const { showAlert } = useAlert()

  return useCallback(async () => {
    const pendingOrder = getPendingOrder()

    if (!pendingOrder?.code) {
      return false
    }

    try {
      const freshOrder = await fetchOrder(pendingOrder.code)

      if (isOrderCheckoutBlocked(freshOrder)) {
        savePendingOrder(freshOrder)
        const shouldPay = shouldContinuePayment(freshOrder)

        showAlert({
          tone: 'warning',
          title: shouldPay ? 'Payment masih tertunda' : 'Pesanan belum selesai',
          message: shouldPay
            ? 'Selesaikan atau batalkan payment sebelumnya dulu.'
            : 'Kamu bisa checkout lagi setelah order sebelumnya delivered.',
        })
        navigate(shouldPay ? '/payment' : getCheckTransactionPath(freshOrder))

        return true
      }

      clearPendingOrder()

      return false
    } catch {
      clearPendingOrder()

      return false
    }
  }, [navigate, showAlert])
}

import { useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAlert } from '../context/useAlert'
import { fetchOrder } from '../services/api'
import {
  clearPendingOrder,
  getPendingOrder,
  savePendingOrder,
} from '../utils/transactions'

function isActivePendingPayment(order) {
  return order?.rawStatus === 'pending_payment'
}

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

      if (isActivePendingPayment(freshOrder)) {
        savePendingOrder(freshOrder)
        showAlert({
          tone: 'warning',
          title: 'Payment masih tertunda',
          message: 'Selesaikan atau batalkan pesanan sebelumnya dulu.',
        })
        navigate('/payment')

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

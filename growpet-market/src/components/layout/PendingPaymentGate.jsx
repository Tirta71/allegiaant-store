import { useEffect, useRef } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { useAlert } from '../../context/useAlert'
import { fetchOrder } from '../../features/orders/orders.api'
import {
  clearPendingOrder,
  getPendingOrder,
  isOrderFinished,
  savePendingOrder,
  shouldContinuePayment,
} from '../../utils/transactions'

function PendingPaymentGate({ children }) {
  const location = useLocation()
  const navigate = useNavigate()
  const { showAlert } = useAlert()
  const lastRedirectKey = useRef('')

  useEffect(() => {
    if (location.pathname === '/payment' || location.pathname === '/cek-transaksi') {
      return undefined
    }

    const pendingOrder = getPendingOrder()

    if (!pendingOrder?.code) {
      return undefined
    }

    let isActive = true

    fetchOrder(pendingOrder.code)
      .then((freshOrder) => {
        if (!isActive) {
          return
        }

        if (isOrderFinished(freshOrder)) {
          clearPendingOrder()
          return
        }

        savePendingOrder(freshOrder)

        if (!shouldContinuePayment(freshOrder)) {
          return
        }

        const redirectKey = `${freshOrder.code}:${location.pathname}`

        if (lastRedirectKey.current !== redirectKey) {
          lastRedirectKey.current = redirectKey
          showAlert({
            tone: 'warning',
            title: 'Payment masih tertunda',
            message: 'Bayar atau batalkan pesanan dulu untuk membuka menu lain.',
          })
        }

        navigate('/payment', { replace: true })
      })
      .catch(() => {
        if (isActive) {
          clearPendingOrder()
        }
      })

    return () => {
      isActive = false
    }
  }, [location.pathname, navigate, showAlert])

  return children
}

export default PendingPaymentGate

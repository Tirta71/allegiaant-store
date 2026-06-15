import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import AlertStack from '../components/ui/AlertStack'
import { AlertContext as AlertStateContext } from './alert-context'

const DEFAULT_DURATION = 3400

function createAlertId() {
  return `${Date.now()}-${Math.random().toString(16).slice(2)}`
}

export function AlertProvider({ children }) {
  const [alerts, setAlerts] = useState([])
  const timersRef = useRef(new Map())

  const dismissAlert = useCallback((alertId) => {
    const timerId = timersRef.current.get(alertId)

    if (timerId) {
      window.clearTimeout(timerId)
      timersRef.current.delete(alertId)
    }

    setAlerts((currentAlerts) =>
      currentAlerts.filter((alert) => alert.id !== alertId),
    )
  }, [])

  const showAlert = useCallback(
    (options) => {
      const nextOptions =
        typeof options === 'string' ? { message: options } : options
      const alert = {
        id: createAlertId(),
        tone: nextOptions.tone || 'info',
        title: nextOptions.title,
        message: nextOptions.message,
      }
      const duration = nextOptions.duration ?? DEFAULT_DURATION

      setAlerts((currentAlerts) => [alert, ...currentAlerts].slice(0, 4))

      if (duration > 0) {
        const timerId = window.setTimeout(() => {
          dismissAlert(alert.id)
        }, duration)
        timersRef.current.set(alert.id, timerId)
      }

      return alert.id
    },
    [dismissAlert],
  )

  useEffect(() => {
    const timers = timersRef.current

    return () => {
      timers.forEach((timerId) => window.clearTimeout(timerId))
      timers.clear()
    }
  }, [])

  const value = useMemo(
    () => ({
      dismissAlert,
      showAlert,
    }),
    [dismissAlert, showAlert],
  )

  return (
    <AlertStateContext.Provider value={value}>
      {children}
      <AlertStack alerts={alerts} onDismiss={dismissAlert} />
    </AlertStateContext.Provider>
  )
}

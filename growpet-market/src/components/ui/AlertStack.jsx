import Alert from './Alert'

function AlertStack({ alerts, onDismiss }) {
  if (alerts.length === 0) {
    return null
  }

  return (
    <div className="alert-stack" aria-live="polite" aria-relevant="additions">
      {alerts.map((alert) => (
        <Alert
          className="alert--toast"
          tone={alert.tone}
          title={alert.title}
          onClose={() => onDismiss(alert.id)}
          key={alert.id}
        >
          {alert.message}
        </Alert>
      ))}
    </div>
  )
}

export default AlertStack

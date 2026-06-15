const ALERT_ICONS = {
  success: 'OK',
  info: 'i',
  warning: '!',
  error: '!',
}

const ALERT_TITLES = {
  success: 'Berhasil',
  info: 'Info market',
  warning: 'Perlu dicek',
  error: 'Ada kendala',
}

function Alert({ children, className = '', onClose, role, title, tone = 'info' }) {
  const alertRole = role || (tone === 'error' ? 'alert' : 'status')

  return (
    <article
      className={`alert alert--${tone} ${className}`.trim()}
      role={alertRole}
    >
      <span className="alert__icon" aria-hidden="true">
        {ALERT_ICONS[tone] || ALERT_ICONS.info}
      </span>
      <div className="alert__body">
        <strong>{title || ALERT_TITLES[tone] || ALERT_TITLES.info}</strong>
        {children && <p>{children}</p>}
      </div>
      {onClose && (
        <button
          type="button"
          className="alert__close"
          aria-label="Tutup alert"
          onClick={onClose}
        >
          x
        </button>
      )}
    </article>
  )
}

export default Alert

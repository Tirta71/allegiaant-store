function Badge({ children, tone = 'mint' }) {
  const normalizedTone = String(tone || 'mint').trim().toLowerCase()

  return <span className={`badge badge--${normalizedTone}`}>{children}</span>
}

export default Badge

function Button({
  as: Component = 'button',
  children,
  className = '',
  size = 'md',
  type,
  variant = 'primary',
  ...props
}) {
  const componentProps =
    Component === 'button' ? { type: type || 'button', ...props } : props

  return (
    <Component
      className={`btn btn--${variant} btn--${size} ${className}`.trim()}
      {...componentProps}
    >
      {children}
    </Component>
  )
}

export default Button

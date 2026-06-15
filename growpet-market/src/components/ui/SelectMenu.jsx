import { useEffect, useId, useRef, useState } from 'react'

function SelectMenu({ options, value, onChange, className = '' }) {
  const [isOpen, setIsOpen] = useState(false)
  const menuRef = useRef(null)
  const listboxId = useId()
  const selectedOption =
    options.find((option) => String(option.value) === String(value)) ||
    options[0]

  useEffect(() => {
    if (!isOpen) {
      return undefined
    }

    function handlePointerDown(event) {
      if (!menuRef.current?.contains(event.target)) {
        setIsOpen(false)
      }
    }

    function handleKeyDown(event) {
      if (event.key === 'Escape') {
        setIsOpen(false)
      }
    }

    document.addEventListener('pointerdown', handlePointerDown)
    document.addEventListener('keydown', handleKeyDown)

    return () => {
      document.removeEventListener('pointerdown', handlePointerDown)
      document.removeEventListener('keydown', handleKeyDown)
    }
  }, [isOpen])

  return (
    <div className={`select-menu ${className}`.trim()} ref={menuRef}>
      <button
        type="button"
        className="select-menu__button"
        aria-haspopup="listbox"
        aria-expanded={isOpen}
        aria-controls={listboxId}
        onClick={() => setIsOpen((currentValue) => !currentValue)}
      >
        <span>{selectedOption.label}</span>
        <span className="select-menu__chevron" aria-hidden="true" />
      </button>

      {isOpen && (
        <div className="select-menu__panel">
          <div className="select-menu__list" id={listboxId} role="listbox">
            {options.map((option) => {
              const isSelected = String(option.value) === String(value)

              return (
                <button
                  type="button"
                  className={isSelected ? 'is-selected' : undefined}
                  role="option"
                  aria-selected={isSelected}
                  onClick={() => {
                    onChange(option.value)
                    setIsOpen(false)
                  }}
                  key={option.value}
                >
                  {option.label}
                </button>
              )
            })}
          </div>
        </div>
      )}
    </div>
  )
}

export default SelectMenu

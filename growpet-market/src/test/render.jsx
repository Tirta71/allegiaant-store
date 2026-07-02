import { render } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'

import { AlertProvider } from '../context/AlertContext'
import { CartProvider } from '../context/CartContext'

export function renderWithApp(ui, options = {}) {
  const { route = '/', withCart = true } = options

  function Providers({ children }) {
    const content = withCart ? <CartProvider>{children}</CartProvider> : children

    return (
      <MemoryRouter initialEntries={[route]}>
        <AlertProvider>{content}</AlertProvider>
      </MemoryRouter>
    )
  }

  return render(ui, { wrapper: Providers })
}

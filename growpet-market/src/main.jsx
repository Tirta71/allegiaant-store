import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import App from './App.jsx'
import './styles/global.css'
import './styles/layout.css'
import './styles/ui.css'
import './styles/alert.css'
import './styles/home.css'
import './styles/pet.css'
import './styles/cart.css'
import './styles/checkout.css'
import './styles/payment.css'
import './styles/transaction.css'
import './styles/token.css'

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <BrowserRouter>
      <App />
    </BrowserRouter>
  </StrictMode>,
)

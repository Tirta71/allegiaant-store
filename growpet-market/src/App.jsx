import Footer from './components/layout/Footer'
import FloatingCart from './components/layout/FloatingCart'
import Navbar from './components/layout/Navbar'
import { AlertProvider } from './context/AlertContext'
import { CartProvider } from './context/CartContext'
import AppRoutes from './routes/AppRoutes'

function App() {
  return (
    <CartProvider>
      <AlertProvider>
        <div className="app-shell">
          <Navbar />
          <main>
            <AppRoutes />
          </main>
          <FloatingCart />
          <Footer />
        </div>
      </AlertProvider>
    </CartProvider>
  )
}

export default App

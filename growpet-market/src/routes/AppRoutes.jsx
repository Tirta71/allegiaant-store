import { Navigate, Route, Routes } from 'react-router-dom'
import BuyGuide from '../pages/BuyGuide'
import Cart from '../pages/Cart'
import CheckTransaction from '../pages/CheckTransaction'
import Checkout from '../pages/Checkout'
import Home from '../pages/Home'
import Payment from '../pages/Payment'
import PetDetail from '../pages/PetDetail'
import TestimonialsPage from '../pages/TestimonialsPage'

function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<Home />} />
      <Route path="/cara-beli" element={<BuyGuide />} />
      <Route path="/testimoni" element={<TestimonialsPage />} />
      <Route path="/catalog" element={<Navigate to="/" replace />} />
      <Route path="/pet/:id" element={<PetDetail />} />
      <Route path="/token" element={<Navigate to="/" replace />} />
      <Route path="/cart" element={<Cart />} />
      <Route path="/checkout" element={<Checkout />} />
      <Route path="/payment" element={<Payment />} />
      <Route path="/cek-transaksi" element={<CheckTransaction />} />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  )
}

export default AppRoutes

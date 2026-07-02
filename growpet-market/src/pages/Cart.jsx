import { Link } from 'react-router-dom'
import CartItem from '../components/cart/CartItem'
import OrderSummary from '../components/cart/OrderSummary'
import Button from '../components/ui/Button'
import PageHeader from '../components/ui/PageHeader'
import { useCart } from '../context/useCart'

function Cart() {
  const { items } = useCart()

  return (
    <div className="container page-flow cart-page">
      <PageHeader
        eyebrow="Cart"
        title="Keranjang pet"
        description="Atur quantity sebelum lanjut ke checkout."
        action={
          <Button as={Link} to="/" variant="ghost" className="back-link">
            Kembali
          </Button>
        }
      />

      {items.length === 0 ? (
        <section className="empty-state">
          <h1>Cart masih kosong</h1>
          <p>Beli pet atau token dari market terlebih dahulu.</p>
          <Button as={Link} to="/">
            Cari pet
          </Button>
        </section>
      ) : (
        <section className="cart-layout">
          <div className="cart-list">
            {items.map((item) => (
              <CartItem item={item} key={item.cartKey} />
            ))}
          </div>
          <OrderSummary />
        </section>
      )}
    </div>
  )
}

export default Cart

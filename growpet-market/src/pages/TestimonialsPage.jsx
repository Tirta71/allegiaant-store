import Testimonials from '../components/home/Testimonials'

function TestimonialsPage() {
  return (
    <div className="container page-flow">
      <section className="market-intro testimonial-hero">
        <div>
          <p className="market-kicker">Customer voices</p>
          <h1>Testimoni pembeli Allegiaant Store</h1>
          <p>
            Beberapa feedback singkat dari pembeli yang memakai katalog pet dan
            checkout manual di store ini.
          </p>
        </div>
        <div className="market-stats" aria-label="Testimonial highlights">
          <div>
            <strong>Fast</strong>
            <span>Checkout</span>
          </div>
          <div>
            <strong>Clean</strong>
            <span>Catalog</span>
          </div>
          <div>
            <strong>Manual</strong>
            <span>Payment</span>
          </div>
        </div>
      </section>

      <Testimonials />
    </div>
  )
}

export default TestimonialsPage

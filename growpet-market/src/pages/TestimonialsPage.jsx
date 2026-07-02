import Testimonials from "../components/home/Testimonials";

function TestimonialsPage() {
  return (
    <div className="container page-flow">
      <section className="market-intro testimonial-hero">
        <div>
          <p className="market-kicker">Customer voices</p>
          <h1>Testimoni pembeli Allegiaant Store</h1>
          <p>
            Bukti trade dari order yang sudah selesai, lengkap dengan username
            Roblox dan item yang dibeli.
          </p>
        </div>
        <div className="market-stats" aria-label="Testimonial highlights">
          <div>
            <strong>Trade</strong>
            <span>Proof</span>
          </div>
          <div>
            <strong>Real</strong>
            <span>Buyer</span>
          </div>
          <div>
            <strong>Order</strong>
            <span>Done</span>
          </div>
        </div>
      </section>

      <Testimonials />
    </div>
  );
}

export default TestimonialsPage;

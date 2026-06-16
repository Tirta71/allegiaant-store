
const testimonials = [
  {
    name: 'Nara',
    order: 'Pet order',
    text: 'Card pet gampang discan, rarity dan stoknya langsung kelihatan tanpa harus buka banyak halaman.',
  },
  {
    name: 'Adit',
    order: 'Token order',
    text: 'Checkout ringkas, nominal dan ringkasan pesanan jelas. Tinggal lanjut payment manual tanpa bingung.',
  },
  {
    name: 'Mika',
    order: 'Mobile buyer',
    text: 'Tampilan mobile-nya clean dan padat. Pilih pet, cek detail, lalu bayar terasa lebih cepat.',
  },
  {
    name: 'Raka',
    order: 'Repeat buyer',
    text: 'Kode transaksi dan status order jelas, jadi enak buat follow up pesanan yang masih pending.',
  },
  {
    name: 'Luna',
    order: 'Pet detail',
    text: 'Detail pet lebih rapi, pilihan mutasi dan beratnya mudah dibandingkan sebelum checkout.',
  },
  {
    name: 'Farel',
    order: 'QRIS payment',
    text: 'Payment manual pakai QRIS statis lebih praktis, total pesanan sudah otomatis muncul.',
  },
]

function Testimonials() {
  return (
    <section className="home-section">
    
      <div className="testimonial-grid">
        {testimonials.map((testimonial) => (
          <article className="testimonial-card" key={testimonial.name}>
            <p>"{testimonial.text}"</p>
            <div>
              <strong>{testimonial.name}</strong>
              <span>{testimonial.order}</span>
            </div>
          </article>
        ))}
      </div>
    </section>
  )
}

export default Testimonials

import SectionHeader from '../ui/SectionHeader'

const testimonials = [
  {
    name: 'Nara',
    text: 'UI-nya gampang discan, pet mahal langsung keliatan rarity dan stoknya.',
  },
  {
    name: 'Adit',
    text: 'Checkout ringkas, cocok buat showcase marketplace tanpa sistem login.',
  },
  {
    name: 'Mika',
    text: 'Warnanya soft dan card pet-nya lucu, tetap bersih di layar mobile.',
  },
]

function Testimonials() {
  return (
    <section className="home-section">
      <SectionHeader
        eyebrow="Testimoni"
        title="Market terasa ringan dan friendly"
        description="Copy dan layout dibuat singkat supaya fokus tetap di katalog pet."
      />
      <div className="testimonial-grid">
        {testimonials.map((testimonial) => (
          <article className="testimonial-card" key={testimonial.name}>
            <p>"{testimonial.text}"</p>
            <strong>{testimonial.name}</strong>
          </article>
        ))}
      </div>
    </section>
  )
}

export default Testimonials

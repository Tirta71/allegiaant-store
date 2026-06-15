import SectionHeader from '../ui/SectionHeader'
import { buyingSteps } from '../../data/howToBuy'

function HowToBuy() {
  return (
    <section className="home-section">
      <SectionHeader
        eyebrow="Cara beli"
        title="Alur beli dari katalog sampai order diproses"
        description="Simpan kode transaksi setelah payment untuk cek status atau chat seller."
      />
      <div className="steps-grid">
        {buyingSteps.map((step, index) => (
          <article className="step-card" key={step.title}>
            <span>{String(index + 1).padStart(2, '0')}</span>
            <h3>{step.title}</h3>
            <p>{step.description}</p>
          </article>
        ))}
      </div>
    </section>
  )
}

export default HowToBuy

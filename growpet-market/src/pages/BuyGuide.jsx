import { Link } from 'react-router-dom'
import Button from '../components/ui/Button'
import SectionHeader from '../components/ui/SectionHeader'
import { buyingSteps, sellerNotes } from '../data/howToBuy'

function BuyGuide() {
  return (
    <div className="container page-flow">
      <section className="buy-guide-hero">
        <div>
          <p className="market-kicker">Cara beli</p>
          <h1>Ikuti flow order dari katalog sampai seller proses item.</h1>
          <p>
            Alurnya dibuat singkat: pilih item, checkout, payment, simpan kode,
            lalu cek transaksi kalau butuh update.
          </p>
        </div>
        <div className="buy-guide-hero__actions">
          <Button as={Link} to="/">
            Mulai belanja
          </Button>
          <Button as={Link} to="/cek-transaksi" variant="ghost">
            Cek transaksi
          </Button>
        </div>
      </section>

      <section className="home-section">
        <SectionHeader
          eyebrow="Step order"
          title="Runtutan beli yang benar"
          description="Pastikan kode transaksi disimpan setelah payment berhasil dikonfirmasi."
        />
        <div className="steps-grid steps-grid--guide">
          {buyingSteps.map((step, index) => (
            <article className="step-card" key={step.title}>
              <span>{String(index + 1).padStart(2, '0')}</span>
              <h3>{step.title}</h3>
              <p>{step.description}</p>
            </article>
          ))}
        </div>
      </section>

      <section className="buy-guide-panel">
        <div>
          <span>Kontak seller</span>
          <h2>Chat WhatsApp setelah cek status.</h2>
          <p>
            Tombol chat seller akan muncul di halaman cek transaksi setelah kamu
            memasukkan kode. Pesannya otomatis membawa kode itu supaya seller
            langsung tahu order mana yang perlu dicek.
          </p>
        </div>
        <div className="buy-guide-notes">
          {sellerNotes.map((note) => (
            <p key={note}>{note}</p>
          ))}
        </div>
      </section>
    </div>
  )
}

export default BuyGuide

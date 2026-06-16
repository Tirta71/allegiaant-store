import { NavLink } from 'react-router-dom'
import { useState } from 'react'

function Navbar() {
  const [isMenuOpen, setIsMenuOpen] = useState(false)

  function closeMenu() {
    setIsMenuOpen(false)
  }

  return (
    <header className="site-header">
      <nav className="navbar container" aria-label="Main navigation">
        <NavLink to="/" className="brand" onClick={closeMenu}>
          <span className="brand__mark" aria-hidden="true">
            A
          </span>
          <span>
            <strong>Allegiaant</strong>
            <small>Allegiaant Store</small>
          </span>
        </NavLink>

        <button
          className={`nav-toggle ${isMenuOpen ? 'nav-toggle--open' : ''}`}
          type="button"
          aria-label="Toggle navigation menu"
          aria-expanded={isMenuOpen}
          onClick={() => setIsMenuOpen((currentValue) => !currentValue)}
        >
          <span />
          <span />
          <span />
        </button>

        <div className={`nav-links ${isMenuOpen ? 'nav-links--open' : ''}`}>
          <NavLink to="/" end onClick={closeMenu}>
            Market
          </NavLink>
          <NavLink to="/cara-beli" onClick={closeMenu}>
            Cara Beli
          </NavLink>
          <NavLink to="/testimoni" onClick={closeMenu}>
            Testimoni
          </NavLink>
          <NavLink to="/cek-transaksi" onClick={closeMenu}>
            Cek Transaksi
          </NavLink>
        </div>
      </nav>
    </header>
  )
}

export default Navbar

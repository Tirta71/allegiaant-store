import { NavLink } from 'react-router-dom'

function Navbar() {
  return (
    <header className="site-header">
      <nav className="navbar container" aria-label="Main navigation">
        <NavLink to="/" className="brand">
          <span className="brand__mark" aria-hidden="true">
            A
          </span>
          <span>
            <strong>Allegiaant</strong>
            <small>Allegiaant Store</small>
          </span>
        </NavLink>

        <div className="nav-links">
          <NavLink to="/" end>
            Market
          </NavLink>
          <NavLink to="/cara-beli">Cara Beli</NavLink>
          <NavLink to="/cek-transaksi">Cek Transaksi</NavLink>
        </div>
      </nav>
    </header>
  )
}

export default Navbar

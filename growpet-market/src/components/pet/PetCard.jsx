import { Link, useNavigate } from 'react-router-dom'
import { formatPrice, formatWeight } from '../../data/pets'
import { usePendingPaymentGuard } from '../../hooks/usePendingPaymentGuard'
import Badge from '../ui/Badge'
import Button from '../ui/Button'
import ProductArt from '../ui/ProductArt'

function PetCard({ pet }) {
  const navigate = useNavigate()
  const guardPendingPayment = usePendingPaymentGuard()
  const detailPath = `/pet/${pet.slug || pet.id}`

  async function handleBuyClick() {
    if (await guardPendingPayment()) {
      return
    }

    navigate(detailPath)
  }

  return (
    <article className="pet-card">
      <Link to={detailPath} className="pet-card__visual-link">
        <ProductArt product={pet} />
      </Link>

      <div className="pet-card__body">
        <div className="pet-card__topline">
          <Badge tone={pet.rarity.toLowerCase()}>{pet.rarity}</Badge>
          <div className="pet-card__stats">
            <span>Stok {pet.stock}</span>
            <span>Terjual {pet.sales}</span>
          </div>
        </div>
        <Link to={detailPath} className="pet-card__title">
          {pet.name}
        </Link>
  
        <div className="pet-card__options">
          <span>{pet.mutations.join(', ') || 'Varian tersedia'}</span>
          <span>
            {pet.weights.length > 0
              ? `${formatWeight(pet.weights[0])} - ${formatWeight(
                  pet.weights[pet.weights.length - 1],
                )}`
              : 'Berat by varian'}
          </span>
        </div>
      </div>

      <div className="pet-card__footer">
        <strong>{pet.price > 0 ? formatPrice(pet.price) : 'Cek detail'}</strong>
        <div>
          <span className="pet-card__mutation-badge">
            {pet.mutations.length} Mutasi
          </span>
          <span className="pet-card__weight-badge">
            {pet.weights.length > 0
              ? `${formatWeight(pet.weights[0])} - ${formatWeight(
                  pet.weights[pet.weights.length - 1],
                )}`
              : 'Varian'}
          </span>
          <Button size="sm" variant="soft" onClick={handleBuyClick}>
            Beli Pet
          </Button>
        </div>
      </div>
    </article>
  )
}

export default PetCard

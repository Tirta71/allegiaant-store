import { Link } from 'react-router-dom'
import { formatPrice, formatWeight } from '../../data/pets'
import Badge from '../ui/Badge'
import Button from '../ui/Button'

function PetCard({ pet }) {
  return (
    <article className="pet-card">
      <Link to={`/pet/${pet.id}`} className="pet-card__visual-link">
        <div
          className="pet-art"
          role="img"
          aria-label={`${pet.name} pet illustration`}
          style={{ '--pet-accent': pet.accent, '--pet-soft': pet.soft }}
        >
          <span>{pet.name.slice(0, 1)}</span>
        </div>
      </Link>

      <div className="pet-card__body">
        <div className="pet-card__topline">
          <Badge tone={pet.rarity.toLowerCase()}>{pet.rarity}</Badge>
          <span>{pet.stock} stock</span>
        </div>
        <Link to={`/pet/${pet.id}`} className="pet-card__title">
          {pet.name}
        </Link>
        <p>{pet.description}</p>
        <div className="pet-card__options">
          <span>{pet.mutations.join(', ')}</span>
          <span>
            {formatWeight(pet.weights[0])} -{' '}
            {formatWeight(pet.weights[pet.weights.length - 1])}
          </span>
        </div>
      </div>

      <div className="pet-card__footer">
        <strong>{formatPrice(pet.price)}</strong>
        <div>
          <span className="pet-card__mutation-badge">
            {pet.mutations.length} Mutasi
          </span>
          <span className="pet-card__weight-badge">
            {formatWeight(pet.weights[0])} -{' '}
            {formatWeight(pet.weights[pet.weights.length - 1])}
          </span>
          <Button as={Link} to={`/pet/${pet.id}`} size="sm" variant="soft">
            Beli Pet
          </Button>
        </div>
      </div>
    </article>
  )
}

export default PetCard

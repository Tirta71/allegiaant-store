import PetCard from './PetCard'

function PetGrid({ pets }) {
  if (pets.length === 0) {
    return (
      <div className="empty-state">
        <h3>Pet tidak ditemukan</h3>
        <p>Coba ubah keyword, rarity, atau sorting.</p>
      </div>
    )
  }

  return (
    <div className="pet-grid">
      {pets.map((pet) => (
        <PetCard pet={pet} key={pet.id} />
      ))}
    </div>
  )
}

export default PetGrid

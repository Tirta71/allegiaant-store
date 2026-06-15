import { useMemo, useState } from 'react'

import Testimonials from '../components/home/Testimonials'
import FilterBar from '../components/pet/FilterBar'
import PetGrid from '../components/pet/PetGrid'
import TokenCard from '../components/token/TokenCard'

import { pets } from '../data/pets'
import { tokenProduct } from '../data/tokens'

function sortPets(items, sortBy) {
  const sortedItems = [...items]

  if (sortBy === 'price-low') {
    return sortedItems.sort((a, b) => a.price - b.price)
  }

  if (sortBy === 'price-high') {
    return sortedItems.sort((a, b) => b.price - a.price)
  }

  if (sortBy === 'name') {
    return sortedItems.sort((a, b) => a.name.localeCompare(b.name))
  }

  return sortedItems.sort((a, b) => b.sales - a.sales)
}

function Home() {
  const [search, setSearch] = useState('')
  const [selectedRarity, setSelectedRarity] = useState('All')
  const [sortBy, setSortBy] = useState('popular')

  const filteredPets = useMemo(() => {
    const query = search.trim().toLowerCase()

    return sortPets(
      pets.filter((pet) => {
        const matchesSearch =
          pet.name.toLowerCase().includes(query) ||
          pet.rarity.toLowerCase().includes(query)
        const matchesRarity =
          selectedRarity === 'All' || pet.rarity === selectedRarity

        return matchesSearch && matchesRarity
      }),
      sortBy,
    )
  }, [search, selectedRarity, sortBy])

  return (
    <div className="container page-flow">
      <section className="market-intro">
        <div>
          <p className="market-kicker">Soft garden pet marketplace</p>
          <h1>Allegiaant Store</h1>
          <p>
            Cari pet atau token Grow a Garden, cek detail, lalu checkout tanpa
            login.
          </p>
        </div>
        <div className="market-stats" aria-label="Market highlights">
          <div>
            <strong>10</strong>
            <span>Pet ready</span>
          </div>
          <div>
            <strong>3</strong>
            <span>Rarity tier</span>
          </div>
          <div>
            <strong>Invoice</strong>
            <span>Cek transaksi</span>
          </div>
        </div>
      </section>

    

      <section className="home-section catalog-section">
        

        <FilterBar
          search={search}
          selectedRarity={selectedRarity}
          sortBy={sortBy}
          onSearchChange={setSearch}
          onRarityChange={setSelectedRarity}
          onSortChange={setSortBy}
        />
        <TokenCard product={tokenProduct} />
        <PetGrid pets={filteredPets} />
      </section>

      <Testimonials />
    </div>
  )
}

export default Home

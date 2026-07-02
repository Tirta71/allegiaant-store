import { useEffect, useMemo, useState } from 'react'

import FilterBar from '../components/pet/FilterBar'
import PetGrid from '../components/pet/PetGrid'
import TokenCard from '../components/token/TokenCard'
import Alert from '../components/ui/Alert'
import Button from '../components/ui/Button'

import { fetchProducts } from '../features/products/products.api'

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
  const [products, setProducts] = useState([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState('')
  const [requestKey, setRequestKey] = useState(0)

  function reloadProducts() {
    setIsLoading(true)
    setError('')
    setRequestKey((currentKey) => currentKey + 1)
  }

  useEffect(() => {
    let isMounted = true

    async function loadProducts() {
      try {
        const nextProducts = await fetchProducts()

        if (isMounted) {
          setProducts(nextProducts)
        }
      } catch (requestError) {
        if (isMounted) {
          setError(requestError.message)
        }
      } finally {
        if (isMounted) {
          setIsLoading(false)
        }
      }
    }

    loadProducts()

    return () => {
      isMounted = false
    }
  }, [requestKey])

  const petProducts = useMemo(
    () => products.filter((product) => product.type === 'pet'),
    [products],
  )
  const tokenProduct = products.find((product) => product.type === 'token')

  const filteredPets = useMemo(() => {
    const query = search.trim().toLowerCase()

    return sortPets(
      petProducts.filter((pet) => {
        const matchesSearch =
          pet.name.toLowerCase().includes(query) ||
          pet.rarity.toLowerCase().includes(query)
        const matchesRarity =
          selectedRarity === 'All' || pet.rarity === selectedRarity

        return matchesSearch && matchesRarity
      }),
      sortBy,
    )
  }, [petProducts, search, selectedRarity, sortBy])

  const rarityCount = new Set(petProducts.map((pet) => pet.rarity)).size

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
            <strong>{petProducts.length}</strong>
            <span>Pet ready</span>
          </div>
          <div>
            <strong>{rarityCount}</strong>
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
        {isLoading && (
          <section className="empty-state">
            <h3>Memuat katalog</h3>
            <p>Mengambil data product dari backend.</p>
          </section>
        )}
        {!isLoading && error && (
          <section className="empty-state">
            <Alert tone="error" title="Katalog gagal dimuat">
              {error}
            </Alert>
            <Button onClick={reloadProducts} variant="secondary">
              Coba lagi
            </Button>
          </section>
        )}
        {!isLoading && !error && tokenProduct?.tokenRateId && (
          <TokenCard product={tokenProduct} />
        )}
        {!isLoading && !error && <PetGrid pets={filteredPets} />}
      </section>
    </div>
  )
}

export default Home

import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import PetDetailInfo from '../components/pet/PetDetailInfo'
import Button from '../components/ui/Button'
import { useCart } from '../context/useCart'
import { useAlert } from '../context/useAlert'
import { getPetVariantPrice, pets } from '../data/pets'

function PetDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { addToCart } = useCart()
  const { showAlert } = useAlert()
  const pet = pets.find((item) => item.id === id)
  const [quantity, setQuantity] = useState(1)
  const [selectedMutation, setSelectedMutation] = useState(
    () => pet?.mutations[0] || 'Nightmare',
  )
  const [selectedWeight, setSelectedWeight] = useState(
    () => pet?.weights[0] || 1,
  )
  const selectedPrice = pet
    ? getPetVariantPrice(pet, selectedMutation, selectedWeight)
    : 0

  if (!pet) {
    return (
      <div className="container page-flow">
        <section className="empty-state">
          <h1>Pet tidak ditemukan</h1>
          <p>Pet ini tidak ada di data lokal.</p>
          <Button as={Link} to="/">
            Kembali ke market
          </Button>
        </section>
      </div>
    )
  }

  function handleQuantityChange(value) {
    const nextValue = Number(value)

    if (Number.isNaN(nextValue)) {
      setQuantity(1)
      return
    }

    setQuantity(Math.max(1, Math.min(nextValue, pet.stock)))
  }

  function handleAddToCart() {
    addToCart(pet, quantity, {
      mutation: selectedMutation,
      weightKg: selectedWeight,
      price: selectedPrice,
    })
    showAlert({
      tone: 'success',
      title: 'Masuk cart',
      message: `${quantity} ${pet.name} ${selectedMutation} siap di checkout.`,
    })
  }

  function handleBuyNow() {
    addToCart(pet, quantity, {
      mutation: selectedMutation,
      weightKg: selectedWeight,
      price: selectedPrice,
    })
    showAlert({
      tone: 'info',
      title: 'Order disiapkan',
      message: `${pet.name} masuk cart. Lengkapi data checkout berikutnya.`,
    })
    navigate('/checkout')
  }

  return (
    <div className="container page-flow">
      <Button as={Link} to="/" variant="ghost" className="back-link">
        Back to market
      </Button>
      <PetDetailInfo
        pet={pet}
        quantity={quantity}
        selectedPrice={selectedPrice}
        selectedMutation={selectedMutation}
        selectedWeight={selectedWeight}
        onQuantityChange={handleQuantityChange}
        onMutationChange={setSelectedMutation}
        onWeightChange={setSelectedWeight}
        onAddToCart={handleAddToCart}
        onBuyNow={handleBuyNow}
      />
    </div>
  )
}

export default PetDetail

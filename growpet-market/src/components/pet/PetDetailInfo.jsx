import { formatPrice, formatWeight } from '../../data/pets'
import Badge from '../ui/Badge'
import Button from '../ui/Button'
import ProductArt from '../ui/ProductArt'
import SelectMenu from '../ui/SelectMenu'

function PetDetailInfo({
  pet,
  quantity,
  selectedPrice,
  selectedMutation,
  selectedWeight,
  onQuantityChange,
  onMutationChange,
  onWeightChange,
  onAddToCart,
  onBuyNow,
  canBuy = true,
}) {
  return (
    <section className="detail-panel">
      <ProductArt className="detail-art" product={pet} />

      <div className="detail-copy">
        <Badge tone={pet.rarity.toLowerCase()}>{pet.rarity}</Badge>
        <h1>{pet.name}</h1>
        <p>{pet.description}</p>

        <div className="detail-stats">
          <div>
            <span>Harga</span>
            <strong>{formatPrice(selectedPrice)}</strong>
          </div>
          <div>
            <span>Stok</span>
            <strong>{pet.stock}</strong>
          </div>
          <div>
            <span>Terjual</span>
            <strong>{pet.sales}</strong>
          </div>
        </div>

        <div className="detail-options">
          <div className="detail-option-group">
            <span>Mutasi pet</span>
            <div className="mutation-picker">
              {pet.mutations.map((mutation) => (
                <button
                  type="button"
                  className={
                    selectedMutation === mutation ? 'is-selected' : undefined
                  }
                  onClick={() => onMutationChange(mutation)}
                  key={mutation}
                >
                  {mutation}
                </button>
              ))}
            </div>
          </div>

        </div>

        <div className="perk-list">
          {pet.perks.map((perk) => (
            <span key={perk}>{perk}</span>
          ))}
        </div>

        <div className="detail-buy-controls">
          <label className="detail-option-group">
            <span>Berat pet</span>
            <SelectMenu
              value={selectedWeight}
              onChange={onWeightChange}
              options={pet.weights.map((weight) => ({
                value: weight,
                label: formatWeight(weight),
              }))}
            />
          </label>

          <div className="detail-option-group">
            <span>Jumlah</span>
            <div className="quantity-control" aria-label="Quantity selector">
              <button
                type="button"
                onClick={() => onQuantityChange(quantity - 1)}
                disabled={!canBuy || quantity <= 1}
              >
                -
              </button>
              <input
                type="number"
                min="1"
                max={pet.stock}
                value={quantity}
                onChange={(event) => onQuantityChange(event.target.value)}
                disabled={!canBuy}
              />
              <button
                type="button"
                onClick={() => onQuantityChange(quantity + 1)}
                disabled={!canBuy || quantity >= pet.stock}
              >
                +
              </button>
            </div>
          </div>
        </div>

        <div className="detail-actions">
          <Button onClick={onAddToCart} disabled={!canBuy}>
            Tambah ke cart
          </Button>
          <Button variant="secondary" onClick={onBuyNow} disabled={!canBuy}>
            Beli sekarang
          </Button>
        </div>
      </div>
    </section>
  )
}

export default PetDetailInfo

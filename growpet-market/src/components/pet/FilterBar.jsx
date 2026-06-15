import { rarityOptions } from '../../data/pets'
import SelectMenu from '../ui/SelectMenu'

const sortOptions = [
  { value: 'popular', label: 'Most popular' },
  { value: 'price-low', label: 'Price low to high' },
  { value: 'price-high', label: 'Price high to low' },
  { value: 'name', label: 'Name A-Z' },
]

function FilterBar({
  search,
  selectedRarity,
  sortBy,
  onSearchChange,
  onRarityChange,
  onSortChange,
}) {
  return (
    <div className="filter-bar" id="market">
      <label>
        <span>Search</span>
        <input
          type="search"
          value={search}
          onChange={(event) => onSearchChange(event.target.value)}
          placeholder="Cari Unicorn, Kitsune..."
        />
      </label>

      <div className="filter-field">
        <span>Rarity</span>
        <SelectMenu
          options={rarityOptions.map((rarity) => ({
            value: rarity,
            label: rarity,
          }))}
          value={selectedRarity}
          onChange={onRarityChange}
        />
      </div>

      <div className="filter-field">
        <span>Sort</span>
        <SelectMenu
          options={sortOptions}
          value={sortBy}
          onChange={onSortChange}
        />
      </div>
    </div>
  )
}

export default FilterBar

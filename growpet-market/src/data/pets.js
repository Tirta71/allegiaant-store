export const mutationOptions = ['Nightmare', 'Venom', 'Rainbow']

const sharedMutations = mutationOptions

const mutationPriceModifiers = {
  Nightmare: 0,
  Venom: 15000,
  Rainbow: 35000,
}

export const pets = [
  {
    id: 'unicorn',
    name: 'Unicorn',
    rarity: 'Legendary',
    price: 145000,
    stock: 8,
    sales: 92,
    featured: true,
    bestSeller: true,
    accent: '#f9a8d4',
    soft: '#fce7f3',
    description:
      'Pet garden fantasy dengan aura lembut, cocok untuk koleksi utama dan tampilan inventory yang manis.',
    perks: ['Aura pastel', 'Trade value tinggi', 'Favorit kolektor'],
    mutations: sharedMutations,
    weights: [1.4, 2.1, 3.6, 5.2],
  },
  {
    id: 'dragonfly',
    name: 'Dragonfly',
    rarity: 'Mythical',
    price: 132000,
    stock: 6,
    sales: 87,
    featured: true,
    bestSeller: true,
    accent: '#38bdf8',
    soft: '#e0f2fe',
    description:
      'Pet cepat dengan nuansa sayap kristal, pas untuk pemain yang suka item langka dan ringan.',
    perks: ['Sayap shimmer', 'Cepat dicari', 'Stok terbatas'],
    mutations: sharedMutations,
    weights: [0.8, 1.3, 2.4, 3.1],
  },
  {
    id: 'queen-bee',
    name: 'Queen Bee',
    rarity: 'Legendary',
    price: 118000,
    stock: 9,
    sales: 80,
    featured: true,
    bestSeller: false,
    accent: '#facc15',
    soft: '#fef9c3',
    description:
      'Pet bernuansa royal garden dengan warna honey gold yang cerah dan cute.',
    perks: ['Royal look', 'Harga stabil', 'Cocok untuk bundle'],
    mutations: sharedMutations,
    weights: [1.1, 1.8, 2.7, 3.4],
  },
  {
    id: 'raccoon',
    name: 'Raccoon',
    rarity: 'Divine',
    price: 215000,
    stock: 3,
    sales: 96,
    featured: true,
    bestSeller: true,
    accent: '#64748b',
    soft: '#e2e8f0',
    description:
      'Pet premium yang sering jadi target utama buyer karena demand dan value-nya tinggi.',
    perks: ['Demand tinggi', 'Premium pick', 'Cepat habis'],
    mutations: sharedMutations,
    weights: [2.2, 3.5, 4.8, 6.3],
  },
  {
    id: 'kitsune',
    name: 'Kitsune',
    rarity: 'Divine',
    price: 235000,
    stock: 4,
    sales: 98,
    featured: true,
    bestSeller: true,
    accent: '#fb7185',
    soft: '#ffe4e6',
    description:
      'Pet fantasy dengan vibe mystical garden, pilihan cantik untuk kolektor serius.',
    perks: ['Mystical aura', 'Best pick', 'Value premium'],
    mutations: sharedMutations,
    weights: [1.9, 3.2, 4.4, 5.8],
  },
  {
    id: 'butterfly',
    name: 'Butterfly',
    rarity: 'Mythical',
    price: 98000,
    stock: 12,
    sales: 74,
    featured: false,
    bestSeller: false,
    accent: '#a78bfa',
    soft: '#ede9fe',
    description:
      'Pet ringan dan cantik dengan palet pastel, ideal untuk starter collection.',
    perks: ['Pastel wings', 'Harga ramah', 'Visual manis'],
    mutations: sharedMutations,
    weights: [0.6, 1.2, 1.9, 2.8],
  },
  {
    id: 'red-fox',
    name: 'Red Fox',
    rarity: 'Legendary',
    price: 108000,
    stock: 10,
    sales: 79,
    featured: false,
    bestSeller: false,
    accent: '#fb923c',
    soft: '#ffedd5',
    description:
      'Pet lincah dengan warna hangat, pas untuk buyer yang ingin legendary entry.',
    perks: ['Warm color', 'Entry legendary', 'Mudah dipaketkan'],
    mutations: sharedMutations,
    weights: [1.3, 2.5, 3.7, 4.9],
  },
  {
    id: 'mimic-octopus',
    name: 'Mimic Octopus',
    rarity: 'Mythical',
    price: 125000,
    stock: 7,
    sales: 83,
    featured: false,
    bestSeller: true,
    accent: '#22c55e',
    soft: '#dcfce7',
    description:
      'Pet unik dengan konsep playful dan rare, cocok untuk variasi koleksi yang beda.',
    perks: ['Unik', 'Playful look', 'Popular trade'],
    mutations: sharedMutations,
    weights: [0.9, 1.6, 2.8, 4.1],
  },
  {
    id: 't-rex',
    name: 'T-Rex',
    rarity: 'Legendary',
    price: 155000,
    stock: 5,
    sales: 89,
    featured: false,
    bestSeller: true,
    accent: '#84cc16',
    soft: '#ecfccb',
    description:
      'Pet dinosaurus favorit dengan karakter kuat dan cocok untuk showcase inventory.',
    perks: ['Dino favorite', 'Look kuat', 'Stok cepat bergerak'],
    mutations: sharedMutations,
    weights: [4.5, 6.8, 8.2, 10.4],
  },
  {
    id: 'spinosaurus',
    name: 'Spinosaurus',
    rarity: 'Divine',
    price: 205000,
    stock: 4,
    sales: 91,
    featured: false,
    bestSeller: true,
    accent: '#14b8a6',
    soft: '#ccfbf1',
    description:
      'Pet dinosaurus divine dengan siluet unik, pilihan premium untuk koleksi besar.',
    perks: ['Divine dino', 'Siluet unik', 'Premium value'],
    mutations: sharedMutations,
    weights: [5.1, 7.4, 9.6, 12.2],
  },
]

export const rarityOptions = ['All', 'Legendary', 'Mythical', 'Divine']

export function formatPrice(price) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(price)
}

export function formatWeight(weight) {
  return `${Number(weight).toLocaleString('id-ID', {
    maximumFractionDigits: 1,
  })} kg`
}

export function getPetVariantPrice(pet, mutation, weightKg) {
  const normalizedWeight = Number(weightKg)
  const overridePrice =
    pet.variantPrices?.[mutation]?.[normalizedWeight] ??
    pet.variantPrices?.[mutation]?.[String(normalizedWeight)]

  if (typeof overridePrice === 'number') {
    return overridePrice
  }

  const baseWeight = Number(pet.weights[0] || normalizedWeight)
  const weightModifier =
    Math.max(0, Math.round(normalizedWeight - baseWeight)) * 1000
  const mutationModifier = mutationPriceModifiers[mutation] || 0

  return pet.price + mutationModifier + weightModifier
}

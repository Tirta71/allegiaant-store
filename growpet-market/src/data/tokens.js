export const tokenProduct = {
  id: 'garden-token',
  type: 'token',
  name: 'Token',
  category: 'Token',
  description:
    'Masukkan nominal pembelian, lalu sistem menghitung token yang didapat.',
  accent: '#bce75d',
  soft: '#f7fbd0',
  stock: 999,
  pricePerThousand: 15000,
}

export function calculateTokenAmount(price) {
  return Math.floor((Number(price) / tokenProduct.pricePerThousand) * 1000)
}

export function formatTokenAmount(amount) {
  return Number(amount).toLocaleString('id-ID')
}

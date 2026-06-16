import { useState } from 'react'

function ProductArt({ className = '', product }) {
  const [failedImage, setFailedImage] = useState(false)
  const imageUrl = product.imageUrl
  const showImage = imageUrl && !failedImage
  const label = product.name || 'Product'

  return (
    <div
      className={`pet-art ${showImage ? 'pet-art--image' : ''} ${className}`.trim()}
      role="img"
      aria-label={`${label} product image`}
      style={{ '--pet-accent': product.accent, '--pet-soft': product.soft }}
    >
      {showImage ? (
        <img
          className="pet-art__image"
          src={imageUrl}
          alt=""
          onError={() => setFailedImage(true)}
        />
      ) : (
        <span>{label.slice(0, 1)}</span>
      )}
    </div>
  )
}

export default ProductArt

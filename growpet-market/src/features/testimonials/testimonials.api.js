import { apiRequest, assetUrl } from '../../api/client'

function normalizeTestimonialItem(item) {
  return {
    id: item.id,
    type: item.type,
    name: item.name,
    mutation: item.mutation || '',
    weightKg: item.weight_kg ? Number(item.weight_kg) : null,
    packageLabel: item.package_label || '',
    tokenAmount: item.token_amount ? Number(item.token_amount) : null,
    quantity: Number(item.quantity || 1),
  }
}

function normalizeTestimonial(testimonial) {
  const proof = testimonial.delivery_proof || testimonial.deliveryProof || {}

  return {
    id: testimonial.id,
    robloxUsername: testimonial.roblox_username || testimonial.robloxUsername || '',
    items: testimonial.items?.map(normalizeTestimonialItem) || [],
    deliveryProof: {
      url: assetUrl(proof.url),
      uploadedAt: proof.uploaded_at || proof.uploadedAt || null,
    },
  }
}

export async function fetchTestimonials() {
  const payload = await apiRequest('testimonials')

  return payload.data.map(normalizeTestimonial)
}

const DEFAULT_API_BASE_URL = '/api'

export const API_BASE_URL = (
  import.meta.env.VITE_API_BASE_URL || DEFAULT_API_BASE_URL
).replace(/\/$/, '')

const API_ORIGIN = (() => {
  try {
    return new URL(API_BASE_URL, window.location.origin).origin
  } catch {
    return window.location.origin
  }
})()

function endpointUrl(endpoint, params = {}) {
  const cleanEndpoint = String(endpoint).replace(/^\//, '')
  const url = new URL(`${API_BASE_URL}/${cleanEndpoint}`, window.location.origin)

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== '') {
      url.searchParams.set(key, value)
    }
  })

  return url
}

export function assetUrl(url) {
  if (!url) {
    return ''
  }

  if (/^(https?:)?\/\//.test(url) || url.startsWith('data:')) {
    return url
  }

  if (url.startsWith('/')) {
    return `${API_ORIGIN}${url}`
  }

  return url
}

export class ApiError extends Error {
  constructor(message, { status, payload } = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.payload = payload
  }
}

export async function apiRequest(endpoint, options = {}) {
  const { params, headers, ...requestOptions } = options
  const isFormData = requestOptions.body instanceof FormData
  const response = await fetch(endpointUrl(endpoint, params), {
    headers: {
      Accept: 'application/json',
      ...(requestOptions.body && !isFormData
        ? { 'Content-Type': 'application/json' }
        : {}),
      ...headers,
    },
    ...requestOptions,
  })

  const payload = await response.json().catch(() => null)

  if (!response.ok) {
    const message =
      Object.values(payload?.errors || {})?.flat()?.[0] ||
      payload?.message ||
      'Request API gagal.'

    throw new ApiError(message, {
      status: response.status,
      payload,
    })
  }

  return payload
}

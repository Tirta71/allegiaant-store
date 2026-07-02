import { beforeEach, describe, expect, it, vi } from 'vitest'

import { API_BASE_URL, ApiError, apiRequest, assetUrl } from './client'

function jsonResponse(body, init = {}) {
  return Promise.resolve(
    new Response(JSON.stringify(body), {
      status: 200,
      headers: { 'Content-Type': 'application/json' },
      ...init,
    }),
  )
}

describe('api client', () => {
  beforeEach(() => {
    globalThis.fetch = vi.fn()
  })

  it('builds endpoint URLs with shared headers and query params', async () => {
    globalThis.fetch.mockResolvedValueOnce(jsonResponse({ data: [] }))

    const response = await apiRequest('products', {
      params: { search: 'raccoon', empty: '' },
    })

    const [url, options] = globalThis.fetch.mock.calls[0]

    expect(response).toEqual({ data: [] })
    expect(String(url)).toContain('/api/products?search=raccoon')
    expect(options.headers.Accept).toBe('application/json')
  })

  it('sets JSON content type only when body is not FormData', async () => {
    globalThis.fetch.mockResolvedValueOnce(jsonResponse({ ok: true }))

    await apiRequest('orders', {
      method: 'POST',
      body: JSON.stringify({ code: 'GPM-TEST' }),
    })

    expect(globalThis.fetch.mock.calls[0][1].headers['Content-Type']).toBe(
      'application/json',
    )

    globalThis.fetch.mockResolvedValueOnce(jsonResponse({ ok: true }))

    await apiRequest('orders/GPM-TEST/payment-proof', {
      method: 'POST',
      body: new FormData(),
    })

    expect(
      globalThis.fetch.mock.calls[1][1].headers['Content-Type'],
    ).toBeUndefined()
  })

  it('throws ApiError with backend validation message', async () => {
    globalThis.fetch.mockResolvedValueOnce(
      jsonResponse(
        {
          message: 'The given data was invalid.',
          errors: { proof: ['Bukti wajib diupload.'] },
        },
        { status: 422 },
      ),
    )

    await expect(apiRequest('orders/GPM-TEST/payment-proof')).rejects.toMatchObject({
      name: 'ApiError',
      message: 'Bukti wajib diupload.',
      status: 422,
    })
  })

  it('converts relative asset paths to the API origin', () => {
    const apiOrigin = new URL(API_BASE_URL, window.location.origin).origin

    expect(assetUrl('/storage/qris.png')).toBe(`${apiOrigin}/storage/qris.png`)
    expect(assetUrl('https://cdn.example.com/proof.png')).toBe(
      'https://cdn.example.com/proof.png',
    )
    expect(assetUrl(null)).toBe('')
  })

  it('exposes ApiError for callers that need status-aware handling', () => {
    const error = new ApiError('Not found', {
      status: 404,
      payload: { message: 'Not found' },
    })

    expect(error).toBeInstanceOf(Error)
    expect(error.status).toBe(404)
    expect(error.payload).toEqual({ message: 'Not found' })
  })
})

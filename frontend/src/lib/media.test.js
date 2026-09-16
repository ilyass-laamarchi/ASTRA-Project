// Unit tests for converting stored media paths into public browser URLs.
import { describe, expect, it } from 'vitest'
import { publicMediaUrl } from './media'

describe('public API media', () => {
  it('resolves Laravel storage paths against the API host', () => {
    expect(publicMediaUrl('/storage/avatars/user.png')).toBe('http://localhost:8000/storage/avatars/user.png')
  })

  it('preserves absolute URLs and an empty fallback', () => {
    expect(publicMediaUrl('https://cdn.example.test/avatar.webp')).toBe('https://cdn.example.test/avatar.webp')
    expect(publicMediaUrl(null)).toBe('')
  })
})

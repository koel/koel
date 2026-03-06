import { describe, expect, it } from 'vitest'
import { base64Decode, base64Encode, md5, uuid } from './crypto'

describe('crypto utils', () => {
  it('generates a UUID', () => {
    const id = uuid()
    expect(id).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/)
  })

  it('generates unique UUIDs', () => {
    const ids = new Set(Array.from({ length: 100 }, () => uuid()))
    expect(ids.size).toBe(100)
  })

  it('computes MD5 hash', () => {
    expect(md5('hello')).toBe('5d41402abc4b2a76b9719d911017c592')
  })

  it('base64 encodes a string', () => {
    expect(base64Encode('hello world')).toBe('aGVsbG8gd29ybGQ=')
  })

  it('base64 decodes a string', () => {
    expect(base64Decode('aGVsbG8gd29ybGQ=')).toBe('hello world')
  })

  it('round-trips unicode through base64', () => {
    const original = 'こんにちは 🎵'
    expect(base64Decode(base64Encode(original))).toBe(original)
  })
})

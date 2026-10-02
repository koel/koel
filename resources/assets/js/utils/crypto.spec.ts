import { describe, expect, it } from 'vite-plus/test'
import { base64Decode, base64Encode, sha256 } from './crypto'

describe('crypto utils', () => {
  it('computes SHA-256 hash', async () => {
    expect(await sha256('hello')).toBe('2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e73043362938b9824')
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

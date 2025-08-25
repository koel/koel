import { describe, expect, it, vi } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { Cache } from './cache'

describe('cache', () => {
  let cache!: Cache

  createHarness({
    beforeEach: () => cache = new Cache(),
    afterEach: () => vi.useRealTimers(),
  })

  it('sets and gets a value', () => {
    cache.set('foo', 'bar')
    expect(cache.get('foo')).toBe('bar')
  })

  it('invalidates an entry after set time', () => {
    vi.useFakeTimers()
    cache.set('foo', 'bar', 999)
    expect(cache.has('foo')).toBe(true)

    vi.advanceTimersByTime(1000 * 1000)
    expect(cache.has('foo')).toBe(false)
  })

  it('removes an entry', () => {
    cache.set('foo', 'bar')
    cache.remove('foo')
    expect(cache.get('foo')).toBeUndefined()
  })

  it('checks an entry\'s presence', () => {
    cache.set('foo', 'bar')
    expect(cache.hit('foo')).toBe(true)
    expect(cache.has('foo')).toBe(true)
    expect(cache.miss('foo')).toBe(false)

    cache.remove('foo')
    expect(cache.hit('foo')).toBe(false)
    expect(cache.has('foo')).toBe(false)
    expect(cache.miss('foo')).toBe(true)
  })

  it('remembers a value', async () => {
    const resolver = vi.fn().mockResolvedValue('bar')
    expect(cache.has('foo')).toBe(false)

    expect(await cache.remember('foo', resolver)).toBe('bar')
    expect(cache.get('foo')).toBe('bar')
  })
})

import { expect, it, vi } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { Cache } from './cache'

new class extends UnitTestCase {
  private cache!: Cache

  protected beforeEach () {
    super.beforeEach(() => this.cache = new Cache())
  }

  protected afterEach () {
    super.afterEach(() => vi.useRealTimers())
  }

  protected test () {
    it('sets and gets a value', () => {
      this.cache.set('foo', 'bar')
      expect(this.cache.get('foo')).toBe('bar')
    })

    it('invalidates an entry after set time', () => {
      vi.useFakeTimers()
      this.cache.set('foo', 'bar', 999)
      expect(this.cache.has('foo')).toBe(true)

      vi.advanceTimersByTime(1000 * 1000)
      expect(this.cache.has('foo')).toBe(false)
    })

    it('removes an entry', () => {
      this.cache.set('foo', 'bar')
      this.cache.remove('foo')
      expect(this.cache.get('foo')).toBeUndefined()
    })

    it('checks an entry\'s presence', () => {
      this.cache.set('foo', 'bar')
      expect(this.cache.hit('foo')).toBe(true)
      expect(this.cache.has('foo')).toBe(true)
      expect(this.cache.miss('foo')).toBe(false)

      this.cache.remove('foo')
      expect(this.cache.hit('foo')).toBe(false)
      expect(this.cache.has('foo')).toBe(false)
      expect(this.cache.miss('foo')).toBe(true)
    })

    it('remembers a value', async () => {
      const resolver = vi.fn().mockResolvedValue('bar')
      expect(this.cache.has('foo')).toBe(false)

      expect(await this.cache.remember('foo', resolver)).toBe('bar')
      expect(this.cache.get('foo')).toBe('bar')
    })
  }
}

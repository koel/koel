import { get, remove, set } from 'local-storage'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { localStorageService } from './localStorageService'

new class extends UnitTestCase {
  protected test () {
    it('gets an existing item from local storage', () => {
      set('foo', 'bar')
      expect(localStorageService.get('foo')).toBe('bar')
    })

    it('returns the default value for a non exising item', () => {
      remove('foo')
      expect(localStorageService.get('foo', 42)).toBe(42)
    })

    it('sets an item into local storage', () => {
      remove('foo')
      localStorageService.set('foo', 42)
      expect(get('foo')).toBe(42)
    })

    it('correctly removes an item from local storage', () => {
      set('foo', 42)
      localStorageService.remove('foo')
      expect(get('foo')).toBeNull()
    })
  }
}

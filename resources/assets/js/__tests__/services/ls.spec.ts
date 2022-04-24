import { get, set, remove } from 'local-storage'
import { localStorageService } from '@/services'

describe('services/ls', () => {
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
})

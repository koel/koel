import { get, set, remove } from 'local-storage'
import { ls } from '@/services'

describe('services/ls', () => {
  it('gets an existing item from local storage', () => {
    set('foo', 'bar')
    expect(ls.get('foo')).toBe('bar')
  })

  it('returns the default value for a non exising item', () => {
    remove('foo')
    expect(ls.get('foo', 42)).toBe(42)
  })

  it('sets an item into local storage', () => {
    remove('foo')
    ls.set('foo', 42)
    expect(get('foo')).toBe(42)
  })

  it('correctly removes an item from local storage', () => {
    set('foo', 42)
    ls.remove('foo')
    expect(get('foo')).toBeNull()
  })
})

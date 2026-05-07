import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'

vi.mock('@/composables/useAuthorization', () => ({
  useAuthorization: () => ({
    currentUser: { value: { id: 42 } },
  }),
}))

import { useLocalStorage } from './useLocalStorage'

describe('useLocalStorage', () => {
  const h = createHarness({
    beforeEach: () => localStorage.clear(),
  })

  it('namespaces keys with current user id by default', () => {
    const { set } = useLocalStorage()
    set('theme', 'dark')
    expect(localStorage.getItem('42::theme')).toBe(JSON.stringify('dark'))
  })

  it('namespaces keys with provided user id', () => {
    const user = h.factory('user').make({ id: '99' })
    const { set } = useLocalStorage(true, user)
    set('volume', 80)
    expect(localStorage.getItem('99::volume')).toBe(JSON.stringify(80))
  })

  it('does not namespace when namespaced is false', () => {
    const { set } = useLocalStorage(false)
    set('global-key', 'value')
    expect(localStorage.getItem('global-key')).toBe(JSON.stringify('value'))
  })

  it('gets namespaced value', () => {
    localStorage.setItem('42::theme', JSON.stringify('stored-value'))
    const { get } = useLocalStorage()
    expect(get('theme')).toBe('stored-value')
  })

  it('returns default value when key not found', () => {
    const { get } = useLocalStorage()
    expect(get('missing', 'fallback')).toBe('fallback')
  })

  it('returns null when key not found and no default', () => {
    const { get } = useLocalStorage()
    expect(get('missing')).toBeNull()
  })

  it('returns null when stored value is not valid JSON', () => {
    localStorage.setItem('42::corrupt', '{not json')
    const { get } = useLocalStorage()
    expect(get('corrupt')).toBeNull()
  })

  it.each([
    ['string', 'dark'],
    ['number', 80],
    ['boolean', true],
  ])('round-trips a %s via set and get', (_label, value) => {
    const { get, set } = useLocalStorage()
    set('scalar', value)
    expect(get('scalar')).toBe(value)
  })

  it('round-trips objects via JSON', () => {
    const { get, set } = useLocalStorage()
    set('preferences', { volume: 80, theme: 'dark' })
    expect(get('preferences')).toEqual({ volume: 80, theme: 'dark' })
  })

  it('removes namespaced key', () => {
    localStorage.setItem('42::theme', JSON.stringify('dark'))
    const { remove } = useLocalStorage()
    remove('theme')
    expect(localStorage.getItem('42::theme')).toBeNull()
  })
})

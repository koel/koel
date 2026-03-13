import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'

const mockGet = vi.fn()
const mockSet = vi.fn()
const mockRemove = vi.fn()

vi.mock('local-storage', () => ({
  get: (...args: any[]) => mockGet(...args),
  set: (...args: any[]) => mockSet(...args),
  remove: (...args: any[]) => mockRemove(...args),
}))

vi.mock('@/composables/useAuthorization', () => ({
  useAuthorization: () => ({
    currentUser: { value: { id: 42 } },
  }),
}))

import { useLocalStorage } from './useLocalStorage'

describe('useLocalStorage', () => {
  const h = createHarness({
    beforeEach: () => {
      mockGet.mockReset()
      mockSet.mockReset()
      mockRemove.mockReset()
    },
  })

  it('namespaces keys with current user id by default', () => {
    const { set } = useLocalStorage()
    set('theme', 'dark')
    expect(mockSet).toHaveBeenCalledWith('42::theme', 'dark')
  })

  it('namespaces keys with provided user id', () => {
    const user = h.factory('user', { id: '99' })
    const { set } = useLocalStorage(true, user)
    set('volume', 80)
    expect(mockSet).toHaveBeenCalledWith('99::volume', 80)
  })

  it('does not namespace when namespaced is false', () => {
    const { set } = useLocalStorage(false)
    set('global-key', 'value')
    expect(mockSet).toHaveBeenCalledWith('global-key', 'value')
  })

  it('gets namespaced value', () => {
    mockGet.mockReturnValue('stored-value')
    const { get } = useLocalStorage()
    expect(get('theme')).toBe('stored-value')
    expect(mockGet).toHaveBeenCalledWith('42::theme')
  })

  it('returns default value when key not found', () => {
    mockGet.mockReturnValue(null)
    const { get } = useLocalStorage()
    expect(get('missing', 'fallback')).toBe('fallback')
  })

  it('returns null when key not found and no default', () => {
    mockGet.mockReturnValue(null)
    const { get } = useLocalStorage()
    expect(get('missing')).toBeNull()
  })

  it('removes namespaced key', () => {
    const { remove } = useLocalStorage()
    remove('theme')
    expect(mockRemove).toHaveBeenCalledWith('42::theme')
  })
})

import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { defaultPreferences, preferenceStore } from '@/stores/preferenceStore'

describe('preferenceStore', () => {
  const h = createHarness({
    beforeEach: () => preferenceStore.init(),
  })

  it('sets preferences and saves the state', async () => {
    const user = h.factory('user').make()
    user.preferences = defaultPreferences
    const mock = h.mock(http, 'patch')
    preferenceStore.set('volume', 5)
    await vi.waitFor(() => expect(mock).toHaveBeenCalledWith('me/preferences', { key: 'volume', value: 5 }))

    preferenceStore.volume = 6
    await vi.waitFor(() => expect(mock).toHaveBeenCalledWith('me/preferences', { key: 'volume', value: 6 }))
  })

  it('does not trigger a request if the value is the same', () => {
    const mock = h.mock(http, 'patch')
    preferenceStore.set('volume', preferenceStore.volume)
    expect(mock).not.toHaveBeenCalled()
  })

  it('returns preference values', () => {
    const mock = h.mock(http, 'patch')
    preferenceStore.set('volume', 4.2)
    expect(mock).toHaveBeenCalledWith('me/preferences', { key: 'volume', value: 4.2 })

    expect(preferenceStore.get('volume')).toBe(4.2)
    expect(preferenceStore.volume).toBe(4.2)
  })
})

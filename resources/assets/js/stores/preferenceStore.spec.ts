import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { http } from '@/services'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { defaultPreferences, preferenceStore } from '.'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => preferenceStore.init())
  }

  protected test () {
    it('sets preferences and saves the state', () => {
      const user = factory<User>('user')
      user.preferences = defaultPreferences
      const mock = this.mock(http, 'patch')
      preferenceStore.set('volume', 5)
      expect(mock).toHaveBeenCalledWith('me/preferences', { key: 'volume', value: 5 })

      // test the proxy
      preferenceStore.volume = 6
      expect(mock).toHaveBeenCalledWith('me/preferences', { key: 'volume', value: 6 })
    })

    it('does not trigger a request if the value is the same', () => {
      const mock = this.mock(http, 'patch')
      preferenceStore.set('volume', preferenceStore.volume)
      expect(mock).not.toHaveBeenCalled()
    })

    it('returns preference values', () => {
      const mock = this.mock(http, 'patch')
      preferenceStore.set('volume', 4.2)
      expect(mock).toHaveBeenCalledWith('me/preferences', { key: 'volume', value: 4.2 })

      expect(preferenceStore.get('volume')).toBe(4.2)
      expect(preferenceStore.volume).toBe(4.2)
    })
  }
}

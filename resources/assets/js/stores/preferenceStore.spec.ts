import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { localStorageService } from '@/services'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { preferenceStore } from '@/stores'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => preferenceStore.init(factory<User>('user', { id: 1 })))
  }

  protected test () {
    it('sets preferences', () => {
      const mock = this.mock(localStorageService, 'set')
      preferenceStore.set('volume', 5)
      expect(mock).toHaveBeenCalledWith('preferences_1', expect.objectContaining({ volume: 5 }))

      // test the proxy
      preferenceStore.volume = 6
      expect(mock).toHaveBeenCalledWith('preferences_1', expect.objectContaining({ volume: 6 }))
    })

    it('returns preference values', () => {
      preferenceStore.set('volume', 4.2)
      expect(preferenceStore.get('volume')).toBe(4.2)

      // test the proxy
      expect(preferenceStore.volume).toBe(4.2)
    })
  }
}

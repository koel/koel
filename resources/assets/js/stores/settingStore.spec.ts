import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { http } from '@/services'
import { settingStore } from '.'

new class extends UnitTestCase {
  protected test () {
    it('initializes the store', () => {
      settingStore.init({ media_path: '/media/path' })
      expect(settingStore.state.media_path).toEqual('/media/path')
    })

    it('updates the media path', async () => {
      this.mock(http, 'put')
      await settingStore.update({ media_path: '/dev/null' })
      expect(settingStore.state.media_path).toEqual('/dev/null')
    })
  }
}

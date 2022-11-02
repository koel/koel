import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { fireEvent } from '@testing-library/vue'
import { socketService, volumeManager } from '@/services'
import { preferenceStore } from '@/stores'
import Volume from './Volume.vue'

new class extends UnitTestCase {
  protected beforeEach (cb?: Closure) {
    super.beforeEach(() => {
      preferenceStore.volume = 5
      volumeManager.init(document.createElement('input'))
    })
  }

  protected test () {
    it('mutes and unmutes', async () => {
      const { getByTitle, html } = this.render(Volume)
      expect(html()).toMatchSnapshot()
      expect(volumeManager.volume.value).toEqual(5)

      await fireEvent.click(getByTitle('Mute'))
      expect(html()).toMatchSnapshot()
      expect(volumeManager.volume.value).toEqual(0)

      await fireEvent.click(getByTitle('Unmute'))
      expect(html()).toMatchSnapshot()
      expect(volumeManager.volume.value).toEqual(5)
    })

    it('sets and broadcasts volume', async () => {
      const setMock = this.mock(volumeManager, 'set')
      const broadCastMock = this.mock(socketService, 'broadcast')
      const { getByRole } = this.render(Volume)

      await fireEvent.update(getByRole('slider'), '4.2')
      await fireEvent.change(getByRole('slider'))

      expect(setMock).toHaveBeenCalledWith(4.2)
      expect(broadCastMock).toHaveBeenCalledWith('SOCKET_VOLUME_CHANGED', 4.2)
    })
  }
}

import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { fireEvent, screen } from '@testing-library/vue'
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
      const { html } = this.render(Volume)
      expect(html()).toMatchSnapshot()
      expect(volumeManager.volume.value).toEqual(5)

      await this.user.click(screen.getByTitle('Mute'))
      expect(html()).toMatchSnapshot()
      expect(volumeManager.volume.value).toEqual(0)

      await this.user.click(screen.getByTitle('Unmute'))
      expect(html()).toMatchSnapshot()
      expect(volumeManager.volume.value).toEqual(5)
    })

    it('sets and broadcasts volume', async () => {
      const setMock = this.mock(volumeManager, 'set')
      const broadCastMock = this.mock(socketService, 'broadcast')
      this.render(Volume)

      await fireEvent.update(screen.getByRole('slider'), '4.2')
      await fireEvent.change(screen.getByRole('slider'))

      expect(setMock).toHaveBeenCalledWith(4.2)
      expect(broadCastMock).toHaveBeenCalledWith('SOCKET_VOLUME_CHANGED', 4.2)
    })
  }
}

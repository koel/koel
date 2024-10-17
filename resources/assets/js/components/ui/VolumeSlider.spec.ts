import { expect, it, vi } from 'vitest'
import { fireEvent, screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { socketService } from '@/services/socketService'
import { volumeManager } from '@/services/volumeManager'
import { preferenceStore } from '@/stores/preferenceStore'
import Volume from './VolumeSlider.vue'

new class extends UnitTestCase {
  protected beforeEach () {
    super.beforeEach(() => {
      vi.useFakeTimers()
      preferenceStore.volume = 5
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
      const broadcastMock = this.mock(socketService, 'broadcast')
      this.render(Volume)

      await fireEvent.update(screen.getByRole('slider'), '4.2')
      vi.runAllTimers()

      expect(volumeManager.volume.value).toBe(4.2)
      expect(broadcastMock).toHaveBeenCalledWith('SOCKET_VOLUME_CHANGED', 4.2)
    })
  }
}

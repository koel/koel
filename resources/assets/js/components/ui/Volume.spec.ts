import { expect, it } from 'vitest'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import { fireEvent } from '@testing-library/vue'
import { playbackService, socketService } from '@/services'
import Volume from './Volume.vue'

new class extends ComponentTestCase {
  protected test () {
    it('mutes and unmutes', async () => {
      const muteMock = this.mock(playbackService, 'mute')
      const unmuteMock = this.mock(playbackService, 'unmute')
      const { getByRole } = this.render(Volume)

      await fireEvent.click(getByRole('button'))
      expect(muteMock).toHaveBeenCalledOnce()

      await fireEvent.click(getByRole('button'))
      expect(unmuteMock).toHaveBeenCalledOnce()
    })

    it('sets and broadcasts volume', async () => {
      const setVolumeMock = this.mock(playbackService, 'setVolume')
      const broadCastMock = this.mock(socketService, 'broadcast')
      const { getByRole } = this.render(Volume)

      await fireEvent.update(getByRole('slider'), '4.2')
      await fireEvent.change(getByRole('slider'))

      expect(setVolumeMock).toHaveBeenCalledWith(4.2)
      expect(broadCastMock).toHaveBeenCalledWith('SOCKET_VOLUME_CHANGED', 4.2)
    })
  }
}

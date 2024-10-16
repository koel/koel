import { waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playbackService } from '@/services/playbackService'
import { volumeManager } from '@/services/volumeManager'
import { preferenceStore } from '@/stores/preferenceStore'
import Component from './index.vue'

new class extends UnitTestCase {
  protected test () {
    it('initializes playback services', async () => {
      const initPlaybackMock = this.mock(playbackService, 'init')
      const initVolumeMock = this.mock(volumeManager, 'init')

      this.render(Component)
      preferenceStore.initialized.value = true

      await waitFor(() => {
        expect(initPlaybackMock).toHaveBeenCalled()
        expect(initVolumeMock).toHaveBeenCalled()
      })
    })
  }
}

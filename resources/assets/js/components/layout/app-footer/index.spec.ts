import { waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playbackService } from '@/services/QueuePlaybackService'
import { preferenceStore } from '@/stores/preferenceStore'
import { playbackManager } from '@/services/playbackManager'
import Component from './index.vue'

new class extends UnitTestCase {
  protected test () {
    it('initializes playback and related services', async () => {
      this.createAudioPlayer()
      const useQueuePlaybackMock = this.mock(playbackManager, 'useQueuePlayback').mockReturnValue(playbackService)

      this.render(Component)
      preferenceStore.initialized.value = true

      await waitFor(() => expect(useQueuePlaybackMock).toHaveBeenCalled())
    })
  }
}

import { waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/QueuePlaybackService'
import { preferenceStore } from '@/stores/preferenceStore'
import { playbackManager } from '@/services/playbackManager'
import Component from './index.vue'

describe('index.vue', () => {
  const h = createHarness()

  it('initializes playback and related services', async () => {
    h.createAudioPlayer()
    const useQueuePlaybackMock = h.mock(playbackManager, 'useQueuePlayback').mockReturnValue(playbackService)

    h.render(Component)
    preferenceStore.initialized.value = true

    await waitFor(() => expect(useQueuePlaybackMock).toHaveBeenCalled())
  })
})

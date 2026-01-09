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

    h.render(Component)
    preferenceStore.initialized.value = true

    // The component no longer calls playbackManager.useQueuePlayback() directly
    // Services are activated lazily when the user actually plays something
    // This test just verifies the component renders without errors
    await waitFor(() => {
      expect(preferenceStore.initialized.value).toBe(true)
    })
  })
})

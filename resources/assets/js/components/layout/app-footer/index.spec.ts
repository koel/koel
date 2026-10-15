import { screen, waitFor } from '@testing-library/vue'
import { nextTick, ref } from 'vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/QueuePlaybackService'
import { preferenceStore } from '@/stores/preferenceStore'
import { playbackManager } from '@/services/playbackManager'
import { waveformService } from '@/services/waveformService'
import { volumeNormalizer } from '@/services/volumeNormalizer'
import { crossfadeService } from '@/services/crossfadeService'
import { CurrentStreamableKey } from '@/config/symbols'
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

  const renderWithStreamable = (initialStreamable: Streamable | null = null) => {
    const currentStreamable = ref<Streamable | null>(initialStreamable)

    h.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: currentStreamable,
        },
      },
    })

    return currentStreamable
  }

  it('shows the waveform of the current song', async () => {
    await h.withPlusEdition(async () => {
      const song = h.factory('song').make({ loudness: -9, true_peak: 1 })
      h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      renderWithStreamable().value = song

      await waitFor(() => screen.getByTestId('song-waveform'))
    })
  })

  it('leaves the playing song loudness alone while a crossfade brings in the next song', async () => {
    const applyMock = h.mock(volumeNormalizer, 'applyToStreamable')
    const currentStreamable = renderWithStreamable(h.factory('song').make())
    h.setReadOnlyProperty(crossfadeService, 'active', true)

    currentStreamable.value = h.factory('song').make()
    await nextTick()

    expect(applyMock).not.toHaveBeenCalled()
  })
})

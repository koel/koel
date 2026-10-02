import { screen, waitFor } from '@testing-library/vue'
import { ref } from 'vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/QueuePlaybackService'
import { preferenceStore } from '@/stores/preferenceStore'
import { playbackManager } from '@/services/playbackManager'
import { waveformService } from '@/services/waveformService'
import { CurrentStreamableKey } from '@/config/symbols'
import Component from './index.vue'

vi.mock('@vueuse/core', async importOriginal => {
  const { ref } = await import('vue')

  return {
    ...(await importOriginal<typeof import('@vueuse/core')>()),
    useElementSize: () => ({ width: ref(1280), height: ref(84) }),
  }
})

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

  it('shows the waveform of an analyzed song', async () => {
    await h.withPlusEdition(async () => {
      const song = h.factory('song').make({ loudness: -9, true_peak: 1 })
      const fetchWaveformMock = h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      renderWithStreamable().value = song

      await waitFor(() => screen.getByTestId('waveform'))
      expect(fetchWaveformMock).toHaveBeenCalledWith(song)
    })
  })

  it('shows the waveform of a song that is already current', async () => {
    await h.withPlusEdition(async () => {
      const song = h.factory('song').make({ loudness: -9, true_peak: 1 })
      const fetchWaveformMock = h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      renderWithStreamable(song)

      await waitFor(() => screen.getByTestId('waveform'))
      expect(fetchWaveformMock).toHaveBeenCalledWith(song)
    })
  })

  it('skips the waveform of an unanalyzed song', async () => {
    await h.withPlusEdition(async () => {
      const fetchWaveformMock = h.mock(waveformService, 'fetchWaveform')

      renderWithStreamable().value = h.factory('song').make({ loudness: null })
      await h.tick()

      expect(fetchWaveformMock).not.toHaveBeenCalled()
    })
  })

  it('skips the waveform without Koel Plus', async () => {
    const fetchWaveformMock = h.mock(waveformService, 'fetchWaveform')

    renderWithStreamable().value = h.factory('song').make({ loudness: -9, true_peak: 1 })
    await h.tick()

    expect(fetchWaveformMock).not.toHaveBeenCalled()
  })
})

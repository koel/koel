import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { waveformService } from '@/services/waveformService'
import Component from './SongWaveform.vue'

vi.mock('@vueuse/core', async importOriginal => {
  const { ref } = await import('vue')

  return {
    ...(await importOriginal<typeof import('@vueuse/core')>()),
    useElementSize: () => ({ width: ref(1280), height: ref(84) }),
  }
})

describe('songWaveform.vue', () => {
  const h = createHarness()

  const renderComponent = (song: Song, progress = 0) => h.render(Component, { props: { song, progress } })

  it('draws the waveform of an analyzed song', async () => {
    await h.withPlusEdition(async () => {
      const song = h.factory('song').make({ loudness: -9, true_peak: 1 })
      const fetchWaveformMock = h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      renderComponent(song, 25)

      await waitFor(() => screen.getByTestId('song-waveform-played'))
      expect(fetchWaveformMock).toHaveBeenCalledWith(song)
      expect(screen.getByTestId('song-waveform-played').style.clipPath).toBe('inset(0 75% 0 0)')
    })
  })

  it('draws the waveform of a new song', async () => {
    await h.withPlusEdition(async () => {
      const firstSong = h.factory('song').make({ loudness: -9, true_peak: 1 })
      const secondSong = h.factory('song').make({ loudness: -12, true_peak: 0 })
      const fetchWaveformMock = h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      const { rerender } = renderComponent(firstSong)
      await rerender({ song: secondSong, progress: 0 })

      await waitFor(() => expect(fetchWaveformMock).toHaveBeenCalledWith(secondSong))
    })
  })

  it('skips an unanalyzed song', async () => {
    await h.withPlusEdition(async () => {
      const fetchWaveformMock = h.mock(waveformService, 'fetchWaveform')

      renderComponent(h.factory('song').make({ loudness: null }))
      await h.tick()

      expect(fetchWaveformMock).not.toHaveBeenCalled()
      expect(screen.queryByTestId('song-waveform')).toBeNull()
    })
  })

  it('skips the waveform without Koel Plus', async () => {
    const fetchWaveformMock = h.mock(waveformService, 'fetchWaveform')

    renderComponent(h.factory('song').make({ loudness: -9, true_peak: 1 }))
    await h.tick()

    expect(fetchWaveformMock).not.toHaveBeenCalled()
    expect(screen.queryByTestId('song-waveform')).toBeNull()
  })
})

import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore } from '@/stores/preferenceStore'
import { waveformService } from '@/services/waveformService'
import { drawWaveform } from '@/utils/waveformCanvas'
import Component from './SongWaveform.vue'

vi.mock('@vueuse/core', async importOriginal => {
  const { ref } = await import('vue')

  return {
    ...(await importOriginal<typeof import('@vueuse/core')>()),
    usePreferredReducedMotion: () => ref('no-preference'),
  }
})

vi.mock('@/utils/waveformCanvas', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/waveformCanvas')>()),
  drawWaveform: vi.fn(),
}))

const drawnWith = (frame: Record<string, unknown>) =>
  expect(drawWaveform).toHaveBeenCalledWith(expect.any(HTMLCanvasElement), expect.objectContaining(frame))

describe('songWaveform.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      preferenceStore.state.show_waveform = true
    },
  })

  const renderComponent = (song: Song, progress = 0) => h.render(Component, { props: { song, progress } })

  it('draws the waveform of an analyzed song', async () => {
    await h.withPlusEdition(async () => {
      const song = h.factory('song').make({ loudness: -9, true_peak: 1 })
      const fetchWaveformMock = h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      renderComponent(song, 25)

      await waitFor(() => drawnWith({ progress: 0.25 }))
      expect(fetchWaveformMock).toHaveBeenCalledWith(song)
    })
  })

  it('keeps the line filling smoothly between progress updates while playing', async () => {
    await h.withPlusEdition(async () => {
      h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      renderComponent(h.factory('song').make({ loudness: -9, true_peak: 1, length: 1, playback_state: 'Playing' }), 25)

      await waitFor(() => drawnWith({ progress: expect.toSatisfy((progress: number) => progress > 0.25) }))
    })
  })

  it('dims the dot and its tail while paused', async () => {
    await h.withPlusEdition(async () => {
      h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      renderComponent(h.factory('song').make({ loudness: -9, true_peak: 1, playback_state: 'Paused' }), 25)

      await waitFor(() => drawnWith({ playedOpacity: expect.toSatisfy((opacity: number) => opacity < 0.5) }))
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

  it('draws the shape of the new song even when it starts like the previous one', async () => {
    await h.withPlusEdition(async () => {
      const firstSong = h.factory('song').make({ loudness: -9, true_peak: 1 })
      const secondSong = h.factory('song').make({ loudness: -9, true_peak: 1 })
      h.mock(waveformService, 'fetchWaveform').mockImplementation(async (song: Song) =>
        song.id === firstSong.id ? [0, 0.2, 0.8, 0.8] : [0, 0.8, 0.2, 0.2],
      )

      const { rerender } = renderComponent(firstSong)
      await waitFor(() => drawnWith({ levels: [expect.closeTo(0.125), expect.closeTo(1)] }))

      await rerender({ song: secondSong, progress: 0 })
      await waitFor(() => drawnWith({ levels: [expect.closeTo(1), expect.closeTo(0.5)] }))
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

  it('skips the waveform when turned off', async () => {
    preferenceStore.state.show_waveform = false

    await h.withPlusEdition(async () => {
      const fetchWaveformMock = h.mock(waveformService, 'fetchWaveform')

      renderComponent(h.factory('song').make({ loudness: -9, true_peak: 1 }))
      await h.tick()

      expect(fetchWaveformMock).not.toHaveBeenCalled()
      expect(screen.queryByTestId('song-waveform')).toBeNull()
    })
  })

  it('drops a waveform that finishes loading after being turned off', async () => {
    await h.withPlusEdition(async () => {
      let resolveFetch: (waveform: number[]) => void = () => {}
      h.mock(waveformService, 'fetchWaveform').mockReturnValue(
        new Promise<number[]>(resolve => (resolveFetch = resolve)),
      )

      renderComponent(h.factory('song').make({ loudness: -9, true_peak: 1 }))
      preferenceStore.state.show_waveform = false
      resolveFetch([0.2, 0.8])
      await h.tick(2)

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

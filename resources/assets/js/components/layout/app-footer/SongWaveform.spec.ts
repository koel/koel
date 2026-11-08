import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it, vi } from 'vite-plus/test'
import { reactive } from 'vue'
import { createHarness } from '@/__tests__/TestHarness'
import { preferenceStore } from '@/stores/preferenceStore'
import { waveformService } from '@/services/waveformService'
import { drawWaveform } from '@/utils/waveformCanvas'
import { getCoverColor } from '@/utils/coverColor'
import { KAMELEON_THEME_ID } from '@/config/themes'
import Component from './SongWaveform.vue'

const motion = vi.hoisted(() => ({ preference: 'no-preference' as 'no-preference' | 'reduce' }))

vi.mock('@vueuse/core', async importOriginal => {
  const { ref } = await import('vue')

  return {
    ...(await importOriginal<typeof import('@vueuse/core')>()),
    usePreferredReducedMotion: () => ref(motion.preference),
  }
})

vi.mock('@/utils/waveformCanvas', async importOriginal => ({
  ...(await importOriginal<typeof import('@/utils/waveformCanvas')>()),
  drawWaveform: vi.fn(),
}))

vi.mock('@/utils/coverColor', () => ({ getCoverColor: vi.fn() }))

const drawnWith = (frame: Record<string, unknown>) =>
  expect(drawWaveform).toHaveBeenCalledWith(expect.any(HTMLCanvasElement), expect.objectContaining(frame))

describe('songWaveform.vue', () => {
  const h = createHarness({
    beforeEach: () => {
      preferenceStore.state.show_waveform = true
      motion.preference = 'no-preference'
      vi.mocked(drawWaveform).mockClear()
      vi.mocked(getCoverColor).mockClear()
    },
    afterEach: () => vi.restoreAllMocks(),
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

  it('stops drawing a paused song once nothing moves, and draws again on a seek', async () => {
    motion.preference = 'reduce'

    await h.withPlusEdition(async () => {
      h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      const song = h.factory('song').make({ loudness: -9, true_peak: 1, playback_state: 'Paused', album_cover: '' })
      const { rerender } = renderComponent(song, 25)
      await waitFor(() => drawnWith({ progress: 0.25 }))

      vi.mocked(drawWaveform).mockClear()
      await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))
      expect(drawWaveform).not.toHaveBeenCalled()

      await rerender({ song, progress: 50 })
      await waitFor(() => drawnWith({ progress: 0.5 }))
    })
  })

  it('skips redrawing while playing until the dot moves far enough to show', async () => {
    vi.spyOn(HTMLElement.prototype, 'clientWidth', 'get').mockReturnValue(100)
    vi.spyOn(HTMLElement.prototype, 'clientHeight', 'get').mockReturnValue(40)

    await h.withPlusEdition(async () => {
      h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      renderComponent(
        h
          .factory('song')
          .make({ loudness: -9, true_peak: 1, length: 3600, playback_state: 'Playing', album_cover: '' }),
        25,
      )

      await waitFor(() => drawnWith({ progress: expect.closeTo(0.25) }))

      await waitFor(
        async () => {
          vi.mocked(drawWaveform).mockClear()
          await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))
          expect(drawWaveform).not.toHaveBeenCalled()
        },
        { timeout: 3000 },
      )
    })
  })

  it('redraws when the display scale changes', async () => {
    vi.spyOn(HTMLElement.prototype, 'clientWidth', 'get').mockReturnValue(100)
    vi.spyOn(HTMLElement.prototype, 'clientHeight', 'get').mockReturnValue(40)
    const pixelRatioMock = vi.spyOn(window, 'devicePixelRatio', 'get').mockReturnValue(1)

    await h.withPlusEdition(async () => {
      h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      renderComponent(
        h
          .factory('song')
          .make({ loudness: -9, true_peak: 1, length: 3600, playback_state: 'Playing', album_cover: '' }),
      )

      await waitFor(
        async () => {
          vi.mocked(drawWaveform).mockClear()
          await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)))
          expect(drawWaveform).not.toHaveBeenCalled()
        },
        { timeout: 3000 },
      )

      pixelRatioMock.mockReturnValue(2)

      await waitFor(() => expect(drawWaveform).toHaveBeenCalled())
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
    motion.preference = 'reduce'

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

  it('takes its color from the album cover on the Kameleon theme', async () => {
    preferenceStore.state.theme = KAMELEON_THEME_ID

    await h.withPlusEdition(async () => {
      h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      vi.mocked(getCoverColor).mockResolvedValue('rgb(200, 50, 100)')

      renderComponent(h.factory('song').make({ loudness: -9, true_peak: 1, album_cover: 'cover.webp' }))

      await waitFor(() => drawnWith({ colors: { accent: [200, 50, 100], line: [200, 50, 100] } }))
      expect(getCoverColor).toHaveBeenCalledWith('cover.webp')
    })
  })

  it('goes back to the theme colors when a paused song loses its cover', async () => {
    preferenceStore.state.theme = KAMELEON_THEME_ID
    motion.preference = 'reduce'

    await h.withPlusEdition(async () => {
      h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])
      vi.mocked(getCoverColor).mockResolvedValue('rgb(200, 50, 100)')

      const song = reactive(
        h.factory('song').make({ loudness: -9, true_peak: 1, playback_state: 'Paused', album_cover: 'cover.webp' }),
      )

      renderComponent(song)
      await waitFor(() => drawnWith({ colors: { accent: [200, 50, 100], line: [200, 50, 100] } }))
      vi.mocked(drawWaveform).mockClear()

      song.album_cover = ''

      await waitFor(() =>
        expect(drawWaveform).toHaveBeenCalledWith(
          expect.any(HTMLCanvasElement),
          expect.objectContaining({
            colors: expect.not.objectContaining({ accent: [200, 50, 100] }),
          }),
        ),
      )
    })
  })

  it('drops a cover color that arrives after leaving the Kameleon theme', async () => {
    preferenceStore.state.theme = KAMELEON_THEME_ID

    await h.withPlusEdition(async () => {
      h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      let resolveCoverColor: (color: string) => void = () => {}
      vi.mocked(getCoverColor).mockReturnValue(new Promise(resolve => (resolveCoverColor = resolve)))

      renderComponent(h.factory('song').make({ loudness: -9, true_peak: 1, album_cover: 'cover.webp' }))
      await waitFor(() => expect(getCoverColor).toHaveBeenCalled())

      preferenceStore.state.theme = 'classic'
      await h.tick()
      resolveCoverColor('rgb(200, 50, 100)')
      await h.tick()
      vi.mocked(drawWaveform).mockClear()
      await waitFor(() => expect(drawWaveform).toHaveBeenCalled())

      expect(drawWaveform).not.toHaveBeenCalledWith(
        expect.any(HTMLCanvasElement),
        expect.objectContaining({ colors: { accent: [200, 50, 100], line: [200, 50, 100] } }),
      )
    })
  })

  it('ignores the album cover on other themes', async () => {
    preferenceStore.state.theme = 'classic'

    await h.withPlusEdition(async () => {
      h.mock(waveformService, 'fetchWaveform').mockResolvedValue([0.2, 0.8])

      renderComponent(h.factory('song').make({ loudness: -9, true_peak: 1, album_cover: 'cover.webp' }))
      await h.tick()

      expect(getCoverColor).not.toHaveBeenCalled()
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

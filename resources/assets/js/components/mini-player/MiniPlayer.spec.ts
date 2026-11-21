import { ref } from 'vue'
import { afterEach, describe, expect, it, vi } from 'vite-plus/test'
import { fireEvent, screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { CurrentStreamableKey } from '@/config/symbols'
import type { BasePlaybackService } from '@/services/BasePlaybackService'
import { playbackManager } from '@/services/playbackManager'
import { playbackService } from '@/services/QueuePlaybackService'
import { playableStore } from '@/stores/playableStore'
import { radioStationStore } from '@/stores/radioStationStore'
import Component from './MiniPlayer.vue'

describe('miniPlayer.vue', () => {
  const h = createHarness()

  const renderComponent = (streamable: Streamable = h.factory('song').make()) => {
    return h.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: ref(streamable),
        },
      },
    })
  }

  afterEach(() => {
    playbackManager.currentService = null
  })

  it.each<[string, 'playPrev' | 'playNext']>([
    ['Play previous in queue', 'playPrev'],
    ['Play next in queue', 'playNext'],
  ])('skips with the "%s" button', async (name, method) => {
    h.createAudioPlayer()
    const skipMock = h.mock(playbackService, method)
    renderComponent()

    await h.user.click(screen.getByRole('button', { name }))

    expect(skipMock).toHaveBeenCalled()
  })

  it('cannot skip a radio station', () => {
    renderComponent(h.factory('radio-station').make())

    expect(screen.getByRole<HTMLButtonElement>('button', { name: 'Play previous in queue' }).disabled).toBe(true)
    expect(screen.getByRole<HTMLButtonElement>('button', { name: 'Play next in queue' }).disabled).toBe(true)
  })

  it('favorites the current song', async () => {
    const song = h.factory('song').make({ favorite: false })
    const toggleMock = h.mock(playableStore, 'toggleFavorite')
    renderComponent(song)

    await h.user.click(screen.getByRole('button', { name: 'Favorite' }))

    expect(toggleMock).toHaveBeenCalledWith(song)
  })

  it('favorites the current radio station', async () => {
    const station = h.factory('radio-station').make({ favorite: false })
    const toggleMock = h.mock(radioStationStore, 'toggleFavorite')
    renderComponent(station)

    await h.user.click(screen.getByRole('button', { name: 'Favorite' }))

    expect(toggleMock).toHaveBeenCalledWith(station)
  })

  it('seeks to the clicked position', async () => {
    const seekTo = vi.fn()
    playbackManager.currentService = {
      media: { currentTime: 0, duration: 296 },
      seekTo,
    } as unknown as BasePlaybackService
    renderComponent()

    const slider = screen.getByRole('slider', { name: 'Seek' })
    vi.spyOn(slider, 'getBoundingClientRect').mockReturnValue({ left: 0, width: 1000 } as DOMRect)

    await fireEvent.click(slider, { clientX: 250 })

    expect(seekTo).toHaveBeenCalledWith(74)
  })
})

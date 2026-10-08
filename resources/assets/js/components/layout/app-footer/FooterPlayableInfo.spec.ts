import { describe, expect, it } from 'vite-plus/test'
import { ref } from 'vue'
import { screen } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { CurrentStreamableKey } from '@/config/symbols'
import { cache } from '@/services/cache'
import Router from '@/router'
import Component from './FooterPlayableInfo.vue'

describe('footerPlayableInfo.vue', () => {
  const h = createHarness()

  it('shows no details without a current playable', () => {
    h.render(Component)

    expect(screen.queryByRole('link')).toBeNull()
  })

  it('links a song to its artist', () => {
    const song = h.factory('song').make()

    h.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: ref(song),
        },
      },
    })

    expect(screen.getByRole('link').getAttribute('href')).toBe(Router.url('artists.show', { id: song.artist_id }))
  })

  it('links an episode to its podcast', () => {
    const episode = h.factory('episode').make()

    h.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: ref(episode),
        },
      },
    })

    expect(screen.getByRole('link').getAttribute('href')).toBe(Router.url('podcasts.show', { id: episode.podcast_id }))
  })

  it('navigates to queue and sets scroll intent on thumbnail click', async () => {
    const song = h.factory('song').make({
      title: 'Test Song',
      album_cover: 'https://via.placeholder.com/150',
      playback_state: 'Playing',
      artist_id: 'test-artist',
      artist_name: 'Test Artist',
    })

    const goMock = h.mock(Router, 'go')
    const setMock = h.mock(cache, 'set')

    const { container } = h.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: ref(song),
        },
      },
    })

    const thumb = container.querySelector('.album-thumb') as HTMLElement
    await h.user.click(thumb)

    expect(setMock).toHaveBeenCalledWith('scroll-to-current-in-queue', true)
    expect(goMock).toHaveBeenCalledWith('/#/queue')
  })

  it('does not navigate or set scroll intent when no playable', async () => {
    const goMock = h.mock(Router, 'go')
    const setMock = h.mock(cache, 'set')

    const { container } = h.render(Component)

    const thumb = container.querySelector('.album-thumb') as HTMLElement
    await h.user.click(thumb)

    expect(goMock).not.toHaveBeenCalled()
    expect(setMock).not.toHaveBeenCalled()
  })
})

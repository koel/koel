import { screen, waitFor } from '@testing-library/vue'
import { ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { playbackService } from '@/services/QueuePlaybackService'
import { CurrentStreamableKey } from '@/symbols'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import Router from '@/router'
import Component from './FooterPlayButton.vue'

describe('footerPlayButton.vue', () => {
  const h = createHarness()

  const renderComponent = (currentPlayable: Playable | null = null) => {
    return h.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: ref(currentPlayable),
        },
      },
    })
  }

  it('toggles the playback of current item', async () => {
    h.createAudioPlayer()

    const toggleMock = h.mock(playbackService, 'toggle')
    renderComponent(h.factory('song'))

    await h.user.click(screen.getByRole('button'))

    expect(toggleMock).toHaveBeenCalled()
  })

  it.each<[ScreenName, MethodOf<typeof playableStore>, Album['id'] | Artist['id'] | Playlist['id']]>([
    ['Album', 'fetchSongsForAlbum', 'foo'],
    ['Artist', 'fetchSongsForArtist', 'foo'],
    ['Playlist', 'fetchForPlaylist', '71d8cd40-20d4-4b17-b460-d30fe5bb7b66'],
  ])('initiates playback for %s screen', async (screenName, fetchMethod, id) => {
    h.createAudioPlayer()

    commonStore.state.song_count = 10
    const songs = h.factory('song', 3)
    const fetchMock = h.mock(playableStore, fetchMethod).mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')

    await h.router.activateRoute({
      screen: screenName,
      path: '_',
    }, { id })

    renderComponent()

    await h.user.click(screen.getByRole('button'))
    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(id)
      expect(playMock).toHaveBeenCalledWith(songs)
      expect(goMock).toHaveBeenCalledWith('/#/queue')
    })
  })

  // @ts-ignore
  it.each<[
    ScreenName,
      typeof playableStore | typeof recentlyPlayedStore,
      MethodOf<typeof playableStore | typeof recentlyPlayedStore>,
  ]>([
    ['Favorites', playableStore, 'fetchFavorites'],
    ['RecentlyPlayed', recentlyPlayedStore, 'fetch'],
  ])('initiates playback for %s screen', async (screenName, store, fetchMethod) => {
    h.createAudioPlayer()

    commonStore.state.song_count = 10
    const songs = h.factory('song', 3)
    const fetchMock = h.mock(store, fetchMethod).mockResolvedValue(songs)
    const playMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')

    await h.router.activateRoute({
      screen: screenName,
      path: '_',
    })

    renderComponent()

    await h.user.click(screen.getByRole('button'))
    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalled()
      expect(playMock).toHaveBeenCalledWith(songs)
      expect(goMock).toHaveBeenCalledWith('/#/queue')
    })
  })

  it('does nothing if there are no songs', async () => {
    h.createAudioPlayer()

    commonStore.state.song_count = 0

    const playMock = h.mock(playbackService, 'queueAndPlay')
    const goMock = h.mock(Router, 'go')

    await h.router.activateRoute({
      screen: 'Songs',
      path: '_',
    })

    renderComponent()

    await h.user.click(screen.getByRole('button'))
    await waitFor(() => {
      expect(playMock).not.toHaveBeenCalled()
      expect(goMock).not.toHaveBeenCalled()
    })
  })
})

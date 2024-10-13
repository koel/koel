import Router from '@/router'
import factory from '@/__tests__/factory'
import { ref } from 'vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playbackService } from '@/services'
import { screen, waitFor } from '@testing-library/vue'
import { CurrentPlayableKey } from '@/symbols'
import { commonStore, favoriteStore, queueStore, recentlyPlayedStore, songStore } from '@/stores'
import FooterPlayButton from './FooterPlayButton.vue'

new class extends UnitTestCase {
  protected test () {
    it('toggles the playback of current song', async () => {
      const toggleMock = this.mock(playbackService, 'toggle')
      this.renderComponent(factory('song'))

      await this.user.click(screen.getByRole('button'))

      expect(toggleMock).toHaveBeenCalled()
    })

    it.each<[ScreenName, MethodOf<typeof songStore>, string | number]>([
      ['Album', 'fetchForAlbum', 42],
      ['Artist', 'fetchForArtist', 42],
      ['Playlist', 'fetchForPlaylist', '71d8cd40-20d4-4b17-b460-d30fe5bb7b66'],
    ])('initiates playback for %s screen', async (screenName, fetchMethod, id) => {
      commonStore.state.song_count = 10
      const songs = factory('song', 3)
      const fetchMock = this.mock(songStore, fetchMethod).mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(Router, 'go')

      await this.router.activateRoute({
        screen: screenName,
        path: '_',
      }, { id: String(id) })

      this.renderComponent()

      await this.user.click(screen.getByRole('button'))
      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(id)
        expect(playMock).toHaveBeenCalledWith(songs)
        expect(goMock).toHaveBeenCalledWith('queue')
      })
    })

    it.each<[
      ScreenName,
        typeof favoriteStore | typeof recentlyPlayedStore,
        MethodOf<typeof favoriteStore | typeof recentlyPlayedStore>,
    ]>([
      ['Favorites', favoriteStore, 'fetch'],
      ['RecentlyPlayed', recentlyPlayedStore, 'fetch'],
    ])('initiates playback for %s screen', async (screenName, store, fetchMethod) => {
      commonStore.state.song_count = 10
      const songs = factory('song', 3)
      const fetchMock = this.mock(store, fetchMethod).mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(Router, 'go')

      await this.router.activateRoute({
        screen: screenName,
        path: '_',
      })

      this.renderComponent()

      await this.user.click(screen.getByRole('button'))
      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalled()
        expect(playMock).toHaveBeenCalledWith(songs)
        expect(goMock).toHaveBeenCalledWith('queue')
      })
    })

    it.each<[ScreenName]>([['Queue'], ['Songs'], ['Albums']])('initiates playback %s screen', async screenName => {
      commonStore.state.song_count = 10
      const songs = factory('song', 3)
      const fetchMock = this.mock(queueStore, 'fetchRandom').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(Router, 'go')

      await this.router.activateRoute({
        screen: screenName,
        path: '_',
      })

      this.renderComponent()

      await this.user.click(screen.getByRole('button'))
      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalled()
        expect(playMock).toHaveBeenCalledWith(songs)
        expect(goMock).toHaveBeenCalledWith('queue')
      })
    })

    it('does nothing if there are no songs', async () => {
      commonStore.state.song_count = 0

      const playMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(Router, 'go')

      await this.router.activateRoute({
        screen: 'Songs',
        path: '_',
      })

      this.renderComponent()

      await this.user.click(screen.getByRole('button'))
      await waitFor(() => {
        expect(playMock).not.toHaveBeenCalled()
        expect(goMock).not.toHaveBeenCalled()
      })
    })
  }

  private renderComponent (currentPlayable: Playable | null = null) {
    return this.render(FooterPlayButton, {
      global: {
        provide: {
          [<symbol>CurrentPlayableKey]: ref(currentPlayable),
        },
      },
    })
  }
}

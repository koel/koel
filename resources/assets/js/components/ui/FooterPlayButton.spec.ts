import { screen, waitFor } from '@testing-library/vue'
import { ref } from 'vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { playbackService } from '@/services/QueuePlaybackService'
import { CurrentStreamableKey } from '@/symbols'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import Router from '@/router'
import Component from './FooterPlayButton.vue'

new class extends UnitTestCase {
  protected test () {
    it('toggles the playback of current item', async () => {
      this.createAudioPlayer()

      const toggleMock = this.mock(playbackService, 'toggle')
      this.renderComponent(factory('song'))

      await this.user.click(screen.getByRole('button'))

      expect(toggleMock).toHaveBeenCalled()
    })

    it.each<[ScreenName, MethodOf<typeof playableStore>, Album['id'] | Artist['id'] | Playlist['id']]>([
      ['Album', 'fetchSongsForAlbum', 'foo'],
      ['Artist', 'fetchSongsForArtist', 'foo'],
      ['Playlist', 'fetchForPlaylist', '71d8cd40-20d4-4b17-b460-d30fe5bb7b66'],
    ])('initiates playback for %s screen', async (screenName, fetchMethod, id) => {
      this.createAudioPlayer()

      commonStore.state.song_count = 10
      const songs = factory('song', 3)
      const fetchMock = this.mock(playableStore, fetchMethod).mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(Router, 'go')

      await this.router.activateRoute({
        screen: screenName,
        path: '_',
      }, { id })

      this.renderComponent()

      await this.user.click(screen.getByRole('button'))
      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(id)
        expect(playMock).toHaveBeenCalledWith(songs)
        expect(goMock).toHaveBeenCalledWith('/#/queue')
      })
    })

    it.each<[
      ScreenName,
        typeof playableStore | typeof recentlyPlayedStore,
        MethodOf<typeof playableStore | typeof recentlyPlayedStore>,
    ]>([
      ['Favorites', playableStore, 'fetchFavorites'],
      ['RecentlyPlayed', recentlyPlayedStore, 'fetch'],
    ])('initiates playback for %s screen', async (screenName, store, fetchMethod) => {
      this.createAudioPlayer()

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
        expect(goMock).toHaveBeenCalledWith('/#/queue')
      })
    })

    it('does nothing if there are no songs', async () => {
      this.createAudioPlayer()

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
    return this.render(Component, {
      global: {
        provide: {
          [<symbol>CurrentStreamableKey]: ref(currentPlayable),
        },
      },
    })
  }
}

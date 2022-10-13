import factory from '@/__tests__/factory'
import { ref } from 'vue'
import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playbackService } from '@/services'
import { fireEvent, getByRole, waitFor } from '@testing-library/vue'
import { CurrentSongKey } from '@/symbols'
import { commonStore, favoriteStore, queueStore, recentlyPlayedStore, songStore } from '@/stores'
import FooterPlayButton from './FooterPlayButton.vue'

new class extends UnitTestCase {
  private renderComponent (currentSong: Song | null = null) {
    return this.render(FooterPlayButton, {
      global: {
        provide: {
          [CurrentSongKey]: ref(currentSong)
        }
      }
    })
  }

  protected test () {
    it('toggles the playback of current song', async () => {
      const toggleMock = this.mock(playbackService, 'toggle')
      const { getByRole } = this.renderComponent(factory<Song>('song'))

      await fireEvent.click(getByRole('button'))

      expect(toggleMock).toHaveBeenCalled()
    })

    it.each<[ScreenName, MethodOf<typeof songStore>]>([
      ['Album', 'fetchForAlbum'],
      ['Artist', 'fetchForArtist'],
      ['Playlist', 'fetchForPlaylist']
    ])('initiates playback for %s screen', async (screen, fetchMethod) => {
      commonStore.state.song_count = 10
      const songs = factory<Song>('song', 3)
      const fetchMock = this.mock(songStore, fetchMethod).mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(this.router, 'go')

      this.router.activateRoute({
        screen,
        path: '_'
      }, { id: '42' })

      const { getByRole } = this.renderComponent()

      await fireEvent.click(getByRole('button'))
      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(42)
        expect(playMock).toHaveBeenCalledWith(songs)
        expect(goMock).toHaveBeenCalledWith('queue')
      })
    })

    it.each<[ScreenName, object, string]>([
      ['Favorites', favoriteStore, 'fetch'],
      ['RecentlyPlayed', recentlyPlayedStore, 'fetch']
    ])('initiates playback for %s screen', async (screen, store, fetchMethod) => {
      commonStore.state.song_count = 10
      const songs = factory<Song>('song', 3)
      const fetchMock = this.mock(store, fetchMethod).mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(this.router, 'go')

      this.router.activateRoute({
        screen,
        path: '_'
      })

      const { getByRole } = this.renderComponent()

      await fireEvent.click(getByRole('button'))
      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalled()
        expect(playMock).toHaveBeenCalledWith(songs)
        expect(goMock).toHaveBeenCalledWith('queue')
      })
    })

    it.each<[ScreenName]>([['Queue'], ['Songs'], ['Albums']])('initiates playback %s screen', async (screen) => {
      commonStore.state.song_count = 10
      const songs = factory<Song>('song', 3)
      const fetchMock = this.mock(queueStore, 'fetchRandom').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(this.router, 'go')

      this.router.activateRoute({
        screen,
        path: '_'
      })

      const { getByRole } = this.renderComponent()

      await fireEvent.click(getByRole('button'))
      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalled()
        expect(playMock).toHaveBeenCalledWith(songs)
        expect(goMock).toHaveBeenCalledWith('queue')
      })
    })

    it('does nothing if there are no songs', async () => {
      commonStore.state.song_count = 0

      const playMock = this.mock(playbackService, 'queueAndPlay')
      const goMock = this.mock(this.router, 'go')

      this.router.activateRoute({
        screen: 'Songs',
        path: '_'
      })

      const { getByRole } = this.renderComponent()

      await fireEvent.click(getByRole('button'))
      await waitFor(() => {
        expect(playMock).not.toHaveBeenCalled()
        expect(goMock).not.toHaveBeenCalled()
      })
    })
  }
}

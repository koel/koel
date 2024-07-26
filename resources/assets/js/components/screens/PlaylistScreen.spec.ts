import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils'
import { screen, waitFor } from '@testing-library/vue'
import { playlistStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import PlaylistScreen from './PlaylistScreen.vue'

let playlist: Playlist

new class extends UnitTestCase {
  protected test () {
    it('renders the playlist', async () => {
      await this.renderComponent(factory('song', 10))

      await waitFor(() => {
        screen.getByTestId('song-list')
        expect(screen.queryByTestId('screen-empty-state')).toBeNull()
      })
    })

    it('displays the empty state if playlist is empty', async () => {
      await this.renderComponent([])

      await waitFor(() => {
        screen.getByTestId('screen-empty-state')
        expect(screen.queryByTestId('song-list')).toBeNull()
      })
    })

    it('downloads the playlist', async () => {
      const downloadMock = this.mock(downloadService, 'fromPlaylist')
      await this.renderComponent(factory('song', 10))

      await this.tick(2)
      await this.user.click(screen.getByRole('button', { name: 'Download All' }))

      await waitFor(() => expect(downloadMock).toHaveBeenCalledWith(playlist))
    })

    it('deletes the playlist', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      await this.renderComponent([])

      await this.user.click(screen.getByRole('button', { name: 'Delete this playlist' }))

      await waitFor(() => expect(emitMock).toHaveBeenCalledWith('PLAYLIST_DELETE', playlist))
    })

    it('refreshes the playlist', async () => {
      const { fetchMock } = await this.renderComponent([])

      await this.user.click(screen.getByRole('button', { name: 'Refresh' }))

      expect(fetchMock).toHaveBeenCalledWith(playlist, true)
    })
  }

  private async renderComponent (songs: Playable[]) {
    playlist = playlist || factory('playlist')
    this.be(factory('user', { id: playlist.user_id }))

    playlistStore.init([playlist])
    playlist.playables = songs

    const fetchMock = this.mock(songStore, 'fetchForPlaylist').mockResolvedValue(songs)

    const rendered = this.render(PlaylistScreen)

    await this.router.activateRoute({
      path: `playlists/${playlist.id}`,
      screen: 'Playlist'
    }, { id: playlist.id })

    await waitFor(() => expect(fetchMock).toHaveBeenCalledWith(playlist, false))

    return { rendered, fetchMock }
  }
}

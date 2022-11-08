import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils'
import { fireEvent, getByTestId, waitFor } from '@testing-library/vue'
import { playlistStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import PlaylistScreen from './PlaylistScreen.vue'

let playlist: Playlist

new class extends UnitTestCase {
  private async renderComponent (songs: Song[]) {
    playlist = playlist || factory<Playlist>('playlist')
    playlistStore.init([playlist])

    const fetchMock = this.mock(songStore, 'fetchForPlaylist').mockResolvedValue(songs)

    const rendered = this.render(PlaylistScreen)

    await this.router.activateRoute({
      path: `playlists/${playlist.id}`,
      screen: 'Playlist'
    }, { id: playlist.id.toString() })

    await waitFor(() => expect(fetchMock).toHaveBeenCalledWith(playlist, false))

    return { rendered, fetchMock }
  }

  protected test () {
    it('renders the playlist', async () => {
      const { rendered: { getByTestId, queryByTestId } } = (await this.renderComponent(factory<Song>('song', 10)))

      await waitFor(() => {
        getByTestId('song-list')
        expect(queryByTestId('screen-empty-state')).toBeNull()
      })
    })

    it('displays the empty state if playlist is empty', async () => {
      const { rendered: { getByTestId, queryByTestId } } = (await this.renderComponent([]))

      await waitFor(() => {
        getByTestId('screen-empty-state')
        expect(queryByTestId('song-list')).toBeNull()
      })
    })

    it('downloads the playlist', async () => {
      const downloadMock = this.mock(downloadService, 'fromPlaylist')
      const { rendered: { getByText } } = (await this.renderComponent(factory<Song>('song', 10)))

      await this.tick()
      await fireEvent.click(getByText('Download All'))

      await waitFor(() => expect(downloadMock).toHaveBeenCalledWith(playlist))
    })

    it('deletes the playlist', async () => {
      const emitMock = this.mock(eventBus, 'emit')
      const { rendered: { getByTitle } } = (await this.renderComponent([]))

      await fireEvent.click(getByTitle('Delete this playlist'))

      await waitFor(() => expect(emitMock).toHaveBeenCalledWith('PLAYLIST_DELETE', playlist))
    })

    it('refreshes the playlist', async () => {
      const { rendered: { getByTitle }, fetchMock } = (await this.renderComponent([]))

      await fireEvent.click(getByTitle('Refresh'))

      expect(fetchMock).toHaveBeenCalledWith(playlist, true)
    })
  }
}

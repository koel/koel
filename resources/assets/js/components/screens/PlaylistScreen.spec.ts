import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { eventBus } from '@/utils'
import { fireEvent, getByTestId, waitFor } from '@testing-library/vue'
import { songStore } from '@/stores'
import { downloadService } from '@/services'
import PlaylistScreen from './PlaylistScreen.vue'

let playlist: Playlist

new class extends UnitTestCase {
  private async renderComponent (songs: Song[]) {
    playlist = playlist || factory<Playlist>('playlist')
    const fetchMock = this.mock(songStore, 'fetchForPlaylist').mockResolvedValue(songs)

    const rendered = this.render(PlaylistScreen)
    eventBus.emit('ACTIVATE_SCREEN', 'Playlist', playlist)

    await waitFor(() => expect(fetchMock).toHaveBeenCalledWith(playlist))

    return { rendered, fetchMock }
  }

  protected test () {
    it('renders the playlist', async () => {
      const { getByTestId, queryByTestId } = (await this.renderComponent(factory<Song>('song', 10))).rendered

      await waitFor(() => {
        getByTestId('song-list')
        expect(queryByTestId('screen-empty-state')).toBeNull()
      })
    })

    it('displays the empty state if playlist is empty', async () => {
      const { getByTestId, queryByTestId } = (await this.renderComponent([])).rendered

      await waitFor(() => {
        getByTestId('screen-empty-state')
        expect(queryByTestId('song-list')).toBeNull()
      })
    })

    it('downloads the playlist', async () => {
      const downloadMock = this.mock(downloadService, 'fromPlaylist')
      const { getByText } = (await this.renderComponent(factory<Song>('song', 10))).rendered

      await this.tick()
      await fireEvent.click(getByText('Download All'))

      await waitFor(() => expect(downloadMock).toHaveBeenCalledWith(playlist))
    })

    it('deletes the playlist', async () => {
      const { getByTitle } = (await this.renderComponent([])).rendered

      // mock *after* rendering to not tamper with "ACTIVATE_SCREEN" emission
      const emitMock = this.mock(eventBus, 'emit')

      await fireEvent.click(getByTitle('Delete this playlist'))

      await waitFor(() => expect(emitMock).toHaveBeenCalledWith('PLAYLIST_DELETE', playlist))
    })
  }
}

import { fireEvent, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { artistStore, commonStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import router from '@/router'
import { eventBus } from '@/utils'
import CloseModalBtn from '@/components/ui/BtnCloseModal.vue'
import ArtistScreen from './ArtistScreen.vue'

let artist: Artist

new class extends UnitTestCase {
  protected async renderComponent () {
    commonStore.state.use_last_fm = true

    artist = factory<Artist>('artist', {
      id: 42,
      name: 'Led Zeppelin',
      album_count: 12,
      song_count: 53,
      length: 40_603
    })

    const resolveArtistMock = this.mock(artistStore, 'resolve').mockResolvedValue(artist)

    const songs = factory<Song>('song', 13)
    const fetchSongsMock = this.mock(songStore, 'fetchForArtist').mockResolvedValue(songs)

    const rendered = this.render(ArtistScreen, {
      props: {
        artist: 42
      },
      global: {
        stubs: {
          CloseModalBtn,
          ArtistInfo: this.stub('artist-info'),
          SongList: this.stub('song-list')
        }
      }
    })

    await waitFor(() => {
      expect(resolveArtistMock).toHaveBeenCalledWith(artist.id)
      expect(fetchSongsMock).toHaveBeenCalledWith(artist.id)
    })

    await this.tick(2)

    return rendered
  }

  protected test () {
    it('shows and hides info', async () => {
      const { getByTitle, getByTestId, queryByTestId } = await this.renderComponent()
      expect(queryByTestId('artist-info')).toBeNull()

      await fireEvent.click(getByTitle('View artist information'))
      expect(queryByTestId('artist-info')).not.toBeNull()

      await fireEvent.click(getByTestId('close-modal-btn'))
      expect(queryByTestId('artist-info')).toBeNull()
    })

    it('downloads', async () => {
      const downloadMock = this.mock(downloadService, 'fromArtist')
      const { getByText } = await this.renderComponent()

      await fireEvent.click(getByText('Download All'))

      expect(downloadMock).toHaveBeenCalledWith(artist)
    })

    it('goes back to list if artist is deleted', async () => {
      const goMock = this.mock(router, 'go')
      const byIdMock = this.mock(artistStore, 'byId', null)
      await this.renderComponent()

      eventBus.emit('SONGS_UPDATED')

      await waitFor(() => {
        expect(byIdMock).toHaveBeenCalledWith(artist.id)
        expect(goMock).toHaveBeenCalledWith('artists')
      })
    })
  }
}

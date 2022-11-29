import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { artistStore, commonStore, songStore } from '@/stores'
import { downloadService } from '@/services'
import { eventBus } from '@/utils'
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

    await this.router.activateRoute({
      path: 'artists/42',
      screen: 'Artist'
    }, { id: '42' })

    this.render(ArtistScreen, {
      global: {
        stubs: {
          ArtistInfo: this.stub('artist-info'),
          SongList: this.stub('song-list'),
          AlbumCard: this.stub('album-card')
        }
      }
    })

    await waitFor(() => {
      expect(resolveArtistMock).toHaveBeenCalledWith(artist.id)
      expect(fetchSongsMock).toHaveBeenCalledWith(artist.id)
    })

    await this.tick(2)
  }

  protected test () {
    it('downloads', async () => {
      const downloadMock = this.mock(downloadService, 'fromArtist')
      await this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Download All' }))

      expect(downloadMock).toHaveBeenCalledWith(artist)
    })

    it('goes back to list if artist is deleted', async () => {
      const goMock = this.mock(this.router, 'go')
      const byIdMock = this.mock(artistStore, 'byId', null)
      await this.renderComponent()

      eventBus.emit('SONGS_UPDATED')

      await waitFor(() => {
        expect(byIdMock).toHaveBeenCalledWith(artist.id)
        expect(goMock).toHaveBeenCalledWith('artists')
      })
    })

    it('shows the song list', async () => {
      await this.renderComponent()
      screen.getByTestId('song-list')
    })
  }
}

import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { songStore } from '@/stores/songStore'
import { downloadService } from '@/services/downloadService'
import { eventBus } from '@/utils/eventBus'
import Router from '@/router'
import Component from './ArtistScreen.vue'

new class extends UnitTestCase {
  protected async renderComponent (tab: 'songs' | 'albums' | 'information' = 'songs') {
    commonStore.state.uses_last_fm = true

    const artist = factory('artist', {
      id: 'foo',
      name: 'Led Zeppelin',
    })

    const resolveArtistMock = this.mock(artistStore, 'resolve').mockResolvedValue(artist)

    const songs = factory('song', 13)
    const fetchSongsMock = this.mock(songStore, 'fetchForArtist').mockResolvedValue(songs)

    await this.router.activateRoute({
      path: `artists/foo/${tab}`,
      screen: 'Artist',
    }, {
      tab,
      id: 'foo',
    })

    const rendered = this.render(Component, {
      global: {
        stubs: {
          ArtistInfo: this.stub('artist-info'),
          SongList: this.stub('song-list'),
          AlbumCard: this.stub('album-card'),
        },
      },
    })

    await waitFor(() => {
      expect(resolveArtistMock).toHaveBeenCalledWith(artist.id)
      expect(fetchSongsMock).toHaveBeenCalledWith(artist.id)
    })

    await this.tick(2)

    return {
      ...rendered,
      artist,
      songs,
      resolveArtistMock,
      fetchSongsMock,
    }
  }

  protected test () {
    it('downloads', async () => {
      const downloadMock = this.mock(downloadService, 'fromArtist')
      const { artist } = await this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Download All' }))

      expect(downloadMock).toHaveBeenCalledWith(artist)
    })

    it('goes back to list if artist is deleted', async () => {
      const goMock = this.mock(Router, 'go')
      const { artist } = await this.renderComponent()

      eventBus.emit('SONGS_UPDATED', {
        songs: [],
        artists: [],
        albums: [],
        removed: {
          albums: [],
          artists: [{
            id: artist.id,
            name: artist.name,
            image: artist.image,
            created_at: artist.created_at,
          }],
        },
      })

      await waitFor(() => expect(goMock).toHaveBeenCalledWith('/#/artists'))
    })

    it('shows the song list', async () => {
      await this.renderComponent()
      screen.getByTestId('song-list')
    })
  }
}

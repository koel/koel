import { orderBy } from 'lodash'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { screen, waitFor } from '@testing-library/vue'
import { queueStore, songStore } from '@/stores'
import { playbackService } from '@/services'
import Thumbnail from './ArtistAlbumThumbnail.vue'

let album: Album
let artist: Artist

new class extends UnitTestCase {
  protected test () {
    it('renders for album', () => {
      expect(this.renderForAlbum().html()).toMatchSnapshot()
    })

    it('renders for artist', () => {
      expect(this.renderForArtist().html()).toMatchSnapshot()
    })

    it('plays album', async () => {
      const songs = factory<Song>('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      this.renderForAlbum()

      await this.user.click(screen.getByRole('button'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(album)
        expect(playMock).toHaveBeenCalledWith(songs)
      })
    })

    it('queues album', async () => {
      const songs = factory<Song>('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForAlbum').mockResolvedValue(songs)
      const queueMock = this.mock(queueStore, 'queue')
      this.renderForAlbum()

      await this.user.keyboard('{Alt>}')
      await this.user.click(screen.getByRole('button'))
      await this.user.keyboard('{/Alt}')

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(album)
        expect(queueMock).toHaveBeenCalledWith(orderBy(songs, ['disc', 'track']))
      })
    })

    it('plays artist', async () => {
      const songs = factory<Song>('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForArtist').mockResolvedValue(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')
      this.renderForArtist()

      await this.user.click(screen.getByRole('button'))

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(artist)
        expect(playMock).toHaveBeenCalledWith(songs)
      })
    })

    it('queues artist', async () => {
      const songs = factory<Song>('song', 10)
      const fetchMock = this.mock(songStore, 'fetchForArtist').mockResolvedValue(songs)
      const queueMock = this.mock(queueStore, 'queue')
      this.renderForArtist()

      await this.user.keyboard('{Alt>}')
      await this.user.click(screen.getByRole('button'))
      await this.user.keyboard('{/Alt}')

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalledWith(artist)
        expect(queueMock).toHaveBeenCalledWith(orderBy(songs, ['album_id', 'disc', 'track']))
      })
    })
  }

  private renderForAlbum () {
    album = factory<Album>('album', {
      name: 'IV',
      cover: 'https://test/album.jpg'
    })

    return this.render(Thumbnail, {
      props: {
        entity: album
      }
    })
  }

  private renderForArtist () {
    artist = factory<Artist>('artist', {
      name: 'Led Zeppelin',
      image: 'https://test/blimp.jpg'
    })

    return this.render(Thumbnail, {
      props: {
        entity: artist
      }
    })
  }
}

import { orderBy } from 'lodash'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
import { playbackService } from '@/services'
import { queueStore } from '@/stores'
import factory from '@/__tests__/factory'
import Thumbnail from './AlbumArtistThumbnail.vue'

let album: Album
let artist: Artist

new class extends ComponentTestCase {
  private renderForAlbum () {
    album = factory<Album>('album', {
      name: 'IV',
      cover: 'https://localhost/album.jpg'
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
      image: 'https://localhost/blimp.jpg'
    })

    return this.render(Thumbnail, {
      props: {
        entity: artist
      }
    })
  }

  protected test () {
    it('renders for album', () => {
      expect(this.renderForAlbum().html()).toMatchSnapshot()
    })

    it('renders for artist', () => {
      expect(this.renderForArtist().html()).toMatchSnapshot()
    })

    it('plays album', async () => {
      const mock = this.mock(playbackService, 'playAllInAlbum')
      const { getByRole } = this.renderForAlbum()

      await fireEvent.click(getByRole('button'))

      expect(mock).toHaveBeenCalledWith(album, false)
    })

    it('queues album', async () => {
      const mock = this.mock(queueStore, 'queue')
      const { getByRole } = this.renderForAlbum()

      await fireEvent.click(getByRole('button'), { altKey: true })

      expect(mock).toHaveBeenCalledWith(orderBy(album.songs, ['disc', 'track']))
    })

    it('plays artist', async () => {
      const mock = this.mock(playbackService, 'playAllByArtist')
      const { getByRole } = this.renderForArtist()

      await fireEvent.click(getByRole('button'))

      expect(mock).toHaveBeenCalledWith(artist, false)
    })

    it('queues artist', async () => {
      const mock = this.mock(queueStore, 'queue')
      const { getByRole } = this.renderForArtist()

      await fireEvent.click(getByRole('button'), { altKey: true })

      expect(mock).toHaveBeenCalledWith(orderBy(artist.songs, ['album_id', 'disc', 'track']))
    })
  }
}

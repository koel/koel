import UnitTestCase from '@/__tests__/UnitTestCase'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import Thumbnail from './AlbumArtistThumbnail.vue'

let album: Album
let artist: Artist

new class extends UnitTestCase {
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
      throw 'Unimplemented'
    })

    it('queues album', async () => {
      throw 'Unimplemented'
    })

    it('plays artist', async () => {
      throw 'Unimplemented'
    })

    it('queues artist', async () => {
      throw 'Unimplemented'
    })
  }
}

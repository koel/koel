import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import FooterMiddlePane from './FooterMiddlePane.vue'

new class extends ComponentTestCase {
  protected test () {
    it('renders without a song', () => {
      expect(this.render(FooterMiddlePane).html()).toMatchSnapshot()
    })

    it('renders with a song', () => {
      const album = factory<Album>('album', {
        id: 42,
        name: 'IV',
        artist: factory<Artist>('artist', {
          id: 104,
          name: 'Led Zeppelin'
        })
      })

      const song = factory<Song>('song', {
        album,
        title: 'Fahrstuhl to Heaven',
        artist: album.artist
      })

      expect(this.render(FooterMiddlePane, {
        props: {
          song
        }
      }).html()).toMatchSnapshot()
    })
  }
}

import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import FooterMiddlePane from './FooterMiddlePane.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders without a song', () => expect(this.render(FooterMiddlePane).html()).toMatchSnapshot())

    it('renders with a song', () => {
      expect(this.render(FooterMiddlePane, {
        props: {
          song: factory<Song>('song', {
            title: 'Fahrstuhl to Heaven',
            artist_name: 'Led Zeppelin',
            artist_id: 3,
            album_name: 'Led Zeppelin IV',
            album_id: 4
          })
        }
      }).html()).toMatchSnapshot()
    })
  }
}

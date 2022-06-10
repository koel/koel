import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import FooterMiddlePane from './FooterMiddlePane.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders without a song', () => {
      expect(this.render(FooterMiddlePane).html()).toMatchSnapshot()
    })

    it('renders with a song', () => {
      const song = factory<Song>('song', {
        title: 'Fahrstuhl to Heaven'
      })

      expect(this.render(FooterMiddlePane, {
        props: {
          song
        }
      }).html()).toMatchSnapshot()
    })
  }
}

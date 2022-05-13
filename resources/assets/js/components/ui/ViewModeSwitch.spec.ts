import { expect, it } from 'vitest'
import UnitTestCase from '@/__tests__/UnitTestCase'
import ViewModeSwitch from './ViewModeSwitch.vue'

new class extends UnitTestCase {
  protected test () {
    it.each<[ArtistAlbumViewMode]>([['thumbnails'], ['list']])('renders %s mode', mode => {
      expect(this.render(ViewModeSwitch, {
        props: {
          mode
        }
      }).html()).toMatchSnapshot()
    })
  }
}

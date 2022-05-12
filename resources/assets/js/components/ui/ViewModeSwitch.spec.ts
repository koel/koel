import { expect, it } from 'vitest'
import ComponentTestCase from '@/__tests__/ComponentTestCase'
import ViewModeSwitch from './ViewModeSwitch.vue'

new class extends ComponentTestCase {
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

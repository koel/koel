import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { preferenceStore } from '@/stores'
import UnitTestCase from '@/__tests__/UnitTestCase'
import FooterExtraControls from './FooterExtraControls.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      preferenceStore.state.showExtraPanel = true

      expect(this.render(FooterExtraControls, {
        props: {
          song: factory<Song>('song', {
            playback_state: 'Playing',
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

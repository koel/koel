import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { CurrentSongKey } from '@/symbols'
import FooterExtraControls from './FooterExtraControls.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      expect(this.render(FooterExtraControls, {
        global: {
          stubs: {
            Equalizer: this.stub('Equalizer'),
            Volume: this.stub('Volume')
          },
          provide: {
            [<symbol>CurrentSongKey]: factory<Song>('song', {
              playback_state: 'Playing'
            })
          }
        }
      }).html()).toMatchSnapshot()
    })
  }
}

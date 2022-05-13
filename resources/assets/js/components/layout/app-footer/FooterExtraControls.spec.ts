import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import { preferenceStore } from '@/stores'
import SoundBar from '@/components/ui/SoundBar.vue'
import Volume from '@/components/ui/Volume.vue'
import LikeButton from '@/components/song/SongLikeButton.vue'
import RepeatModeSwitch from '@/components/ui/RepeatModeSwitch.vue'
import Equalizer from '@/components/ui/Equalizer.vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import FooterExtraControls from './FooterExtraControls.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders', () => {
      preferenceStore.state.showExtraPanel = true

      expect(this.render(FooterExtraControls, {
        props: {
          song: factory<Song>('song', {
            playbackState: 'Playing',
            // Set these values for Like button's rendered HTML to be consistent
            title: 'Fahrstuhl to Heaven',
            artist: factory<Artist>('artist', {
              name: 'Led Zeppelin'
            })
          })
        },
        global: {
          stubs: {
            SoundBar,
            Volume,
            LikeButton,
            RepeatModeSwitch,
            Equalizer
          }
        }
      }).html()).toMatchSnapshot()
    })
  }
}

import { beforeEach, expect, it } from 'vitest'
import { cleanup } from '@testing-library/vue'
import { render } from '@/__tests__/__helpers__'
import factory from '@/__tests__/factory'
import { preferenceStore } from '@/stores'
import FooterExtraControls from './FooterExtraControls.vue'
import SoundBar from '@/components/ui/SoundBar.vue'
import Volume from '@/components/ui/Volume.vue'
import LikeButton from '@/components/song/SongLikeButton.vue'
import RepeatModeSwitch from '@/components/ui/RepeatModeSwitch.vue'
import Equalizer from '@/components/ui/Equalizer.vue'

beforeEach(() => cleanup())

it('renders', () => {
  preferenceStore.state.showExtraPanel = true

  expect(render(FooterExtraControls, {
    props: {
      song: factory<Song>('song', {
        playbackState: 'Playing',
        // Set these values for Like button's rendered HTML to be consistent
        title: 'Fahrstühl to Heaven',
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

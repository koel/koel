import { ref } from 'vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { CurrentSongKey } from '@/symbols'
import { fireEvent } from '@testing-library/vue'
import { playbackService } from '@/services'
import FooterPlaybackControls from './FooterPlaybackControls.vue'

new class extends UnitTestCase {
  private renderComponent (song?: Song | null) {
    if (song === undefined) {
      song = factory<Song>('song', {
        id: 42,
        title: 'Fahrstuhl to Heaven',
        artist_name: 'Led Zeppelin',
        artist_id: 3,
        album_name: 'Led Zeppelin IV',
        album_id: 4,
        liked: true
      })
    }

    return this.render(FooterPlaybackControls, {
      global: {
        stubs: {
          PlayButton: this.stub('PlayButton')
        },
        provide: {
          [CurrentSongKey]: ref(song)
        }
      }
    })
  }

  protected test () {
    it('renders without a current song', () => expect(this.renderComponent(null).html()).toMatchSnapshot())
    it('renders with a current song', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('plays the previous song', async () => {
      const playMock = this.mock(playbackService, 'playPrev')
      const { getByTitle } = this.renderComponent()

      await fireEvent.click(getByTitle('Play previous song'))

      expect(playMock).toHaveBeenCalled()
    })

    it('plays the next song', async () => {
      const playMock = this.mock(playbackService, 'playNext')
      const { getByTitle } = this.renderComponent()

      await fireEvent.click(getByTitle('Play next song'))

      expect(playMock).toHaveBeenCalled()
    })
  }
}

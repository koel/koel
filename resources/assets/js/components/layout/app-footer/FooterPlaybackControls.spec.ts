import { ref } from 'vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { CurrentPlayableKey } from '@/symbols'
import { playbackService } from '@/services'
import { screen } from '@testing-library/vue'
import Component from './FooterPlaybackControls.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders without a current playable', () => expect(this.renderComponent(null).html()).toMatchSnapshot())
    it('renders with a current playable', () => expect(this.renderComponent().html()).toMatchSnapshot())

    it('plays the previous song', async () => {
      const playMock = this.mock(playbackService, 'playPrev')
      this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Play previous in queue' }))

      expect(playMock).toHaveBeenCalled()
    })

    it('plays the next playable', async () => {
      const playMock = this.mock(playbackService, 'playNext')
      this.renderComponent()

      await this.user.click(screen.getByRole('button', { name: 'Play next in queue' }))

      expect(playMock).toHaveBeenCalled()
    })
  }

  private renderComponent (playable?: Playable | null) {
    if (playable === undefined) {
      playable = factory('song', {
        id: '00000000-0000-0000-0000-000000000000',
        title: 'Fahrstuhl to Heaven',
        artist_name: 'Led Zeppelin',
        artist_id: 3,
        album_name: 'Led Zeppelin IV',
        album_id: 4,
        liked: true
      })
    }

    return this.render(Component, {
      global: {
        stubs: {
          PlayButton: this.stub('PlayButton')
        },
        provide: {
          [<symbol>CurrentPlayableKey]: ref(playable)
        }
      }
    })
  }
}

import factory from '@/__tests__/factory'
import { playbackService } from '@/services'
import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SongCard from './SongCard.vue'

let playable: Playable

new class extends UnitTestCase {
  protected test () {
    it('has a thumbnail and a like button', () => {
      this.renderComponent()
      screen.getByTestId('thumbnail')
      screen.getByTestId('like-button')
    })

    it('queues and plays on double-click', async () => {
      const playMock = this.mock(playbackService, 'play')
      this.renderComponent()

      await this.user.dblClick(screen.getByRole('article'))

      expect(playMock).toHaveBeenCalledWith(playable)
    })
  }

  private renderComponent (playbackState: PlaybackState = 'Stopped') {
    playable = factory('song', {
      playback_state: playbackState,
      play_count: 10,
      title: 'Foo bar'
    })

    return this.render(SongCard, {
      props: {
        playable
      },
      global: {
        stubs: {
          SongThumbnail: this.stub('thumbnail'),
          LikeButton: this.stub('like-button')
        }
      }
    })
  }
}

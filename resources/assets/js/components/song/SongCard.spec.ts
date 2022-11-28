import factory from '@/__tests__/factory'
import { queueStore } from '@/stores'
import { playbackService } from '@/services'
import { expect, it } from 'vitest'
import { screen } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import SongCard from './SongCard.vue'

let song: Song

new class extends UnitTestCase {
  private renderComponent (playbackState: PlaybackState = 'Stopped') {
    song = factory<Song>('song', {
      playback_state: playbackState,
      play_count: 10,
      title: 'Foo bar'
    })

    return this.render(SongCard, {
      props: {
        song,
        topPlayCount: 42
      },
      global: {
        stubs: {
          SongThumbnail: this.stub('thumbnail'),
          LikeButton: this.stub('like-button')
        }
      }
    })
  }

  protected test () {
    it('has a thumbnail and a like button', () => {
      this.renderComponent()
      screen.getByTestId('thumbnail')
      screen.getByTestId('like-button')
    })

    it('queues and plays on double-click', async () => {
      const queueMock = this.mock(queueStore, 'queueIfNotQueued')
      const playMock = this.mock(playbackService, 'play')
      this.renderComponent()

      await this.user.dblClick(screen.getByRole('article'))

      expect(queueMock).toHaveBeenCalledWith(song)
      expect(playMock).toHaveBeenCalledWith(song)
    })
  }
}

import factory from '@/__tests__/factory'
import { queueStore } from '@/stores'
import { playbackService } from '@/services'
import { expect, it } from 'vitest'
import { fireEvent } from '@testing-library/vue'
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
      }
    })
  }

  protected test () {
    it('queues and plays', async () => {
      const queueMock = this.mock(queueStore, 'queueIfNotQueued')
      const playMock = this.mock(playbackService, 'play')
      const { getByTestId } = this.renderComponent()

      await fireEvent.dblClick(getByTestId('song-card'))

      expect(queueMock).toHaveBeenCalledWith(song)
      expect(playMock).toHaveBeenCalledWith(song)
    })

    it.each<[PlaybackState, MethodOf<typeof playbackService>]>([
      ['Stopped', 'play'],
      ['Playing', 'pause'],
      ['Paused', 'resume']
    ])('if state is currently "%s", %ss', async (state: PlaybackState, method: MethodOf<typeof playbackService>) => {
      const mock = this.mock(playbackService, method)
      const { getByTestId } = this.renderComponent(state)

      await fireEvent.click(getByTestId('play-control'))

      expect(mock).toHaveBeenCalled()
    })
  }
}

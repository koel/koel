import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playbackService } from '@/services'
import { fireEvent } from '@testing-library/vue'
import SongThumbnail from '@/components/song/SongThumbnail.vue'

let song: Song

new class extends UnitTestCase {
  private renderComponent (playbackState: PlaybackState = 'Stopped') {
    song = factory<Song>('song', {
      playback_state: playbackState,
      play_count: 10,
      title: 'Foo bar'
    })

    return this.render(SongThumbnail, {
      props: {
        song
      }
    })
  }

  protected test () {
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

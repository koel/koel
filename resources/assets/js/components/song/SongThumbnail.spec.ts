import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playbackService } from '@/services'
import { screen } from '@testing-library/vue'
import { queueStore } from '@/stores'
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
    it.each<[PlaybackState, string, MethodOf<typeof playbackService>]>([
      ['Stopped', 'Play', 'play'],
      ['Playing', 'Pause', 'pause'],
      ['Paused', 'Resume', 'resume']
    ])('if state is currently "%s", %ss', async (state, name, method) => {
      this.mock(queueStore, 'queueIfNotQueued')
      const playbackMock = this.mock(playbackService, method)
      this.renderComponent(state)

      await this.user.click(screen.getByRole('button', { name }))

      expect(playbackMock).toHaveBeenCalled()
    })
  }
}

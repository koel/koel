import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { playbackService } from '@/services'
import { screen } from '@testing-library/vue'
import { queueStore } from '@/stores'
import Component from './SongThumbnail.vue'

let playable: Playable

new class extends UnitTestCase {
  protected test () {
    it.each<[PlaybackState, MethodOf<typeof playbackService>]>([
      ['Stopped', 'play'],
      ['Playing', 'pause'],
      ['Paused', 'resume']
    ])('if state is currently "%s", %ss', async (state, method) => {
      this.mock(queueStore, 'queueIfNotQueued')
      const playbackMock = this.mock(playbackService, method)
      this.renderComponent(state)

      await this.user.click(screen.getByRole('button'))

      expect(playbackMock).toHaveBeenCalled()
    })
  }

  private renderComponent (playbackState: PlaybackState = 'Stopped') {
    playable = factory('song', {
      playback_state: playbackState,
      play_count: 10,
      title: 'Foo bar'
    })

    return this.render(Component, {
      props: {
        playable
      }
    })
  }
}

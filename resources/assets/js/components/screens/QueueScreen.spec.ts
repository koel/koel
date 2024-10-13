import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore, queueStore } from '@/stores'
import { screen, waitFor } from '@testing-library/vue'
import { playbackService } from '@/services'
import Component from './QueueScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders the queue', () => {
      this.renderComponent(factory('song', 3))

      expect(screen.queryByTestId('song-list')).toBeTruthy()
      expect(screen.queryByTestId('screen-empty-state')).toBeNull()
    })

    it('renders an empty state if no songs queued', () => {
      this.renderComponent([])

      expect(screen.queryByTestId('song-list')).toBeNull()
      expect(screen.queryByTestId('screen-empty-state')).toBeTruthy()
    })

    it('has an option to plays some random songs if the library is not empty', async () => {
      commonStore.state.song_count = 300
      const fetchRandomMock = this.mock(queueStore, 'fetchRandom')
      const playMock = this.mock(playbackService, 'playFirstInQueue')

      this.renderComponent([])
      await this.user.click(screen.getByText('playing some random songs'))

      await waitFor(() => {
        expect(fetchRandomMock).toHaveBeenCalled()
        expect(playMock).toHaveBeenCalled()
      })
    })

    it('shuffles all', async () => {
      const songs = factory('song', 3)
      this.renderComponent(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      await this.user.click(screen.getByTitle('Shuffle all. Press Alt/⌥ to change mode.'))
      await waitFor(() => expect(playMock).toHaveBeenCalledWith(songs, true))
    })
  }

  private renderComponent (playables: Playable[]) {
    queueStore.state.playables = playables

    this.render(Component, {
      global: {
        stubs: {
          SongList: this.stub('song-list'),
        },
      },
    })
  }
}

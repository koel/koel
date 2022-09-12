import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore, queueStore } from '@/stores'
import { fireEvent, waitFor } from '@testing-library/vue'
import { playbackService } from '@/services'
import QueueScreen from './QueueScreen.vue'

new class extends UnitTestCase {
  private renderComponent (songs: Song[]) {
    queueStore.state.songs = songs

    return this.render(QueueScreen, {
      global: {
        stubs: {
          SongList: this.stub('song-list')
        }
      }
    })
  }

  protected test () {
    it('renders the queue', () => {
      const { queryByTestId } = this.renderComponent(factory<Song>('song', 3))

      expect(queryByTestId('song-list')).toBeTruthy()
      expect(queryByTestId('screen-empty-state')).toBeNull()
    })

    it('renders an empty state if no songs queued', () => {
      const { queryByTestId } = this.renderComponent([])

      expect(queryByTestId('song-list')).toBeNull()
      expect(queryByTestId('screen-empty-state')).toBeTruthy()
    })

    it('has an option to plays some random songs if the library is not empty', async () => {
      commonStore.state.song_count = 300
      const fetchRandomMock = this.mock(queueStore, 'fetchRandom')
      const playMock = this.mock(playbackService, 'playFirstInQueue')

      const { getByText } = this.renderComponent([])
      await fireEvent.click(getByText('playing some random songs'))

      await waitFor(() => {
        expect(fetchRandomMock).toHaveBeenCalled()
        expect(playMock).toHaveBeenCalled()
      })
    })

    it('Shuffles all', async () => {
      const songs = factory<Song>('song', 3)
      const { getByTitle } = this.renderComponent(songs)
      const playMock = this.mock(playbackService, 'queueAndPlay')

      await fireEvent.click(getByTitle('Shuffle all songs'))
      await waitFor(() => expect(playMock).toHaveBeenCalledWith(songs, true))
    })
  }
}

import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { commonStore, queueStore, songStore } from '@/stores'
import { screen, waitFor } from '@testing-library/vue'
import { playbackService } from '@/services'
import AllSongsScreen from './AllSongsScreen.vue'

new class extends UnitTestCase {
  private async renderComponent () {
    commonStore.state.song_count = 420
    commonStore.state.song_length = 123_456
    songStore.state.songs = factory<Song>('song', 20)
    const fetchMock = this.mock(songStore, 'paginate').mockResolvedValue(2)

    this.router.$currentRoute.value = {
      screen: 'Songs',
      path: '/songs'
    }

    const rendered = this.render(AllSongsScreen, {
      global: {
        stubs: {
          SongList: this.stub('song-list')
        }
      }
    })

    await waitFor(() => expect(fetchMock).toHaveBeenCalledWith('title', 'asc', 1))
    return rendered
  }

  protected test () {
    it('renders', async () => {
      const { html } = await this.renderComponent()
      await waitFor(() => expect(html()).toMatchSnapshot())
    })

    it('shuffles', async () => {
      const queueMock = this.mock(queueStore, 'fetchRandom')
      const playMock = this.mock(playbackService, 'playFirstInQueue')
      const goMock = this.mock(this.router, 'go')
      await this.renderComponent()

      await this.user.click(screen.getByTitle('Shuffle all songs'))

      await waitFor(() => {
        expect(queueMock).toHaveBeenCalled()
        expect(playMock).toHaveBeenCalled()
        expect(goMock).toHaveBeenCalledWith('queue')
      })
    })
  }
}

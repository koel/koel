import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { recentlyPlayedStore } from '@/stores'
import { screen, waitFor } from '@testing-library/vue'
import RecentlyPlayedScreen from './RecentlyPlayedScreen.vue'

new class extends UnitTestCase {
  private async renderComponent (songs: Song[]) {
    recentlyPlayedStore.state.songs = songs
    const fetchMock = this.mock(recentlyPlayedStore, 'fetch')

    this.render(RecentlyPlayedScreen, {
      global: {
        stubs: {
          SongList: this.stub('song-list')
        }
      }
    })

    await this.router.activateRoute({ path: 'recently-played', screen: 'RecentlyPlayed' })

    await waitFor(() => expect(fetchMock).toHaveBeenCalled())
  }

  protected test () {
    it('displays the songs', async () => {
      await this.renderComponent(factory<Song>('song', 3))

      screen.getByTestId('song-list')
      expect(screen.queryByTestId('screen-empty-state')).toBeNull()
    })

    it('displays the empty state', async () => {
      await this.renderComponent([])

      expect(screen.queryByTestId('song-list')).toBeNull()
      screen.getByTestId('screen-empty-state')
    })
  }
}

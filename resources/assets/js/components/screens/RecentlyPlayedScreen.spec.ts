import { ref } from 'vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { recentlyPlayedStore } from '@/stores'
import { waitFor } from '@testing-library/vue'
import { ActiveScreenKey } from '@/symbols'
import RecentlyPlayedScreen from './RecentlyPlayedScreen.vue'

new class extends UnitTestCase {
  private async renderComponent (songs: Song[]) {
    recentlyPlayedStore.state.songs = songs
    const fetchMock = this.mock(recentlyPlayedStore, 'fetch')

    const rendered = this.render(RecentlyPlayedScreen, {
      global: {
        stubs: {
          SongList: this.stub('song-list')
        },
        provide: {
          [<symbol>ActiveScreenKey]: ref('RecentlyPlayed')
        }
      }
    })

    await waitFor(() => expect(fetchMock).toHaveBeenCalled())

    return rendered
  }

  protected test () {
    it('displays the songs', async () => {
      const { queryByTestId } = await this.renderComponent(factory<Song>('song', 3))

      expect(queryByTestId('song-list')).toBeTruthy()
      expect(queryByTestId('screen-empty-state')).toBeNull()
    })

    it('displays the empty state', async () => {
      const { queryByTestId } = await this.renderComponent([])

      expect(queryByTestId('song-list')).toBeNull()
      expect(queryByTestId('screen-empty-state')).toBeTruthy()
    })
  }
}

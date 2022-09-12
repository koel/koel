import { ref } from 'vue'
import { waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { favoriteStore } from '@/stores'
import { ActiveScreenKey } from '@/symbols'
import FavoritesScreen from './FavoritesScreen.vue'

new class extends UnitTestCase {
  private async renderComponent () {
    const fetchMock = this.mock(favoriteStore, 'fetch')
    const rendered = this.render(FavoritesScreen, {
      global: {
        provide: {
          [<symbol>ActiveScreenKey]: ref('Favorites')
        }
      }
    })

    await waitFor(() => expect(fetchMock).toHaveBeenCalled())

    return rendered
  }

  protected test () {
    it('renders a list of favorites', async () => {
      favoriteStore.state.songs = factory<Song>('song', 13)
      const { queryByTestId } = await this.renderComponent()

      await waitFor(() => {
        expect(queryByTestId('screen-empty-state')).toBeNull()
        expect(queryByTestId('song-list')).not.toBeNull()
      })
    })

    it('shows empty state', async () => {
      favoriteStore.state.songs = []
      const { queryByTestId } = await this.renderComponent()

      expect(queryByTestId('screen-empty-state')).not.toBeNull()
      expect(queryByTestId('song-list')).toBeNull()
    })
  }
}

import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { favoriteStore } from '@/stores'
import FavoritesScreen from './FavoritesScreen.vue'

new class extends UnitTestCase {
  private async renderComponent () {
    const fetchMock = this.mock(favoriteStore, 'fetch')
    this.render(FavoritesScreen)

    await this.router.activateRoute({ path: 'favorites', screen: 'Favorites' })

    await waitFor(() => expect(fetchMock).toHaveBeenCalled())
  }

  protected test () {
    it('renders a list of favorites', async () => {
      favoriteStore.state.songs = factory<Song>('song', 13)
      await this.renderComponent()

      await waitFor(() => {
        expect(screen.queryByTestId('screen-empty-state')).toBeNull()
        screen.getByTestId('song-list')
      })
    })

    it('shows empty state', async () => {
      favoriteStore.state.songs = []
      await this.renderComponent()

      screen.getByTestId('screen-empty-state')
      expect(screen.queryByTestId('song-list')).toBeNull()
    })
  }
}

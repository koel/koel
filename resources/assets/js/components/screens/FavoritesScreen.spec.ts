import { screen, waitFor } from '@testing-library/vue'
import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { songStore } from '@/stores/songStore'
import Component from './FavoritesScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders a list of favorites', async () => {
      songStore.state.favorites = factory('song', 13)
      await this.renderComponent()

      await waitFor(() => {
        expect(screen.queryByTestId('screen-empty-state')).toBeNull()
        screen.getByTestId('song-list')
      })
    })

    it('shows empty state', async () => {
      songStore.state.favorites = []
      await this.renderComponent()

      screen.getByTestId('screen-empty-state')
      expect(screen.queryByTestId('song-list')).toBeNull()
    })
  }

  private async renderComponent () {
    const fetchMock = this.mock(songStore, 'fetchFavorites')
    this.render(Component)

    await this.router.activateRoute({ path: 'favorites', screen: 'Favorites' })

    await waitFor(() => expect(fetchMock).toHaveBeenCalled())
  }
}

import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { playableStore } from '@/stores/playableStore'
import Component from './FavoritesScreen.vue'

describe('favoritesScreen.vue', () => {
  const h = createHarness()

  const renderComponent = async (favorites?: Playable[]) => {
    favorites = favorites ?? h.factory('song', 13)
    playableStore.state.favorites = favorites

    const fetchMock = h.mock(playableStore, 'fetchFavorites').mockResolvedValue(favorites)

    h.render(Component)
    h.visit('/favorites')

    await waitFor(() => expect(fetchMock).toHaveBeenCalled())
  }

  it('renders a list of favorites', async () => {
    await renderComponent()

    await waitFor(() => {
      expect(screen.queryByTestId('screen-empty-state')).toBeNull()
      screen.getByTestId('song-list')
    })
  })

  it('shows empty state', async () => {
    await renderComponent([])

    screen.getByTestId('screen-empty-state')
    expect(screen.queryByTestId('song-list')).toBeNull()
  })
})

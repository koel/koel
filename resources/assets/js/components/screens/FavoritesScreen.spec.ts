import { screen, waitFor } from '@testing-library/vue'
import type { Mock } from 'vite-plus/test'
import { beforeAll, describe, expect, it, vi } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { assertOpenContextMenu } from '@/__tests__/assertions'
import { useContextMenu } from '@/composables/useContextMenu'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import FavoritesContextMenu from '@/components/playable/FavoritesContextMenu.vue'
import Component from './FavoritesScreen.vue'

vi.mock('@/composables/useContextMenu')

describe('favoritesScreen.vue', () => {
  const h = createHarness()

  beforeAll(async () => await import('@/components/playable/playable-list/PlayableList.vue'))

  const renderComponent = async (favorites?: Playable[]) => {
    favorites = favorites ?? h.factory('song').make(13)
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

  it('drops a song unfavorited while the screen is open', async () => {
    const favorites = h.factory('song').make(3)
    await renderComponent(favorites)
    await waitFor(() => expect(screen.getAllByTestId('song-item')).toHaveLength(3))

    playableStore.state.favorites = favorites.slice(1)

    await waitFor(() => expect(screen.getAllByTestId('song-item')).toHaveLength(2))
  })

  it('opens the More Actions menu', async () => {
    commonStore.state.allows_download = true
    commonStore.state.supports_batch_downloading = true
    const { openContextMenu } = useContextMenu()
    const favorites = h.factory('song').make(3)
    await renderComponent(favorites)

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'More Actions' }))
      await assertOpenContextMenu(openContextMenu as Mock, FavoritesContextMenu, { playables: favorites })
    })
  })

  it('hides the More Actions button when the menu would be empty', async () => {
    commonStore.state.allows_download = false
    commonStore.state.allows_embedding = false
    await renderComponent()

    await waitFor(() => screen.getByTestId('song-list'))
    expect(screen.queryByRole('button', { name: 'More Actions' })).toBeNull()
  })
})

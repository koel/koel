import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { overviewStore } from '@/stores/overviewStore'
import Component from './PlayableShelf.vue'

describe('playableShelf.vue', () => {
  const h = createHarness()

  const renderComponent = (props: Record<string, unknown>) =>
    h.render(Component, { props: { header: 'Shelf', emptyText: 'Nothing here.', ...props } })

  it('displays the songs', async () => {
    overviewStore.state.mostPlayedSongs = h.factory('song').make(6)
    renderComponent({ stateKey: 'mostPlayedSongs' })

    await waitFor(() => expect(screen.getAllByTestId('song-card')).toHaveLength(6))
  })

  it('renders a skeleton while loading', () => {
    overviewStore.state.recentlyAddedSongs = h.factory('song').make(6)
    renderComponent({ stateKey: 'recentlyAddedSongs', loading: true })

    screen.getByRole('status')
    expect(screen.queryByTestId('song-card')).toBeNull()
  })

  it('marks the grid as busy during refresh', async () => {
    overviewStore.state.randomSongs = h.factory('song').make(6)
    const refreshMock = h.mock(overviewStore, 'refreshRandomSongs').mockReturnValue(new Promise(() => {}))
    renderComponent({ stateKey: 'randomSongs', refresh: () => overviewStore.refreshRandomSongs() })

    await h.user.click(screen.getByRole('button', { name: 'Refresh' }))

    expect(refreshMock).toHaveBeenCalled()
    await waitFor(() => {
      const grid = screen.getAllByTestId('song-card')[0].closest('ol')!
      expect(grid.getAttribute('aria-busy')).toBe('true')
    })
  })
})

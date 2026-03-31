import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { overviewStore } from '@/stores/overviewStore'
import { screen, waitFor } from '@testing-library/vue'
import Component from './RandomSongs.vue'

describe('randomSongs.vue', () => {
  const h = createHarness()

  it('displays the songs', async () => {
    overviewStore.state.randomSongs = h.factory('song', 6)
    h.render(Component)
    await waitFor(() => expect(screen.getAllByTestId('song-card')).toHaveLength(6))
  })

  it('refreshes random songs on button click', async () => {
    overviewStore.state.randomSongs = h.factory('song', 6)
    const refreshMock = h.mock(overviewStore, 'refreshRandomSongs')
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Refresh' }))

    expect(refreshMock).toHaveBeenCalled()
  })

  it('marks grid as busy during refresh', async () => {
    overviewStore.state.randomSongs = h.factory('song', 6)
    h.mock(overviewStore, 'refreshRandomSongs').mockReturnValue(new Promise(() => {}))
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'Refresh' }))

    await waitFor(() => {
      const grid = screen.getAllByTestId('song-card')[0].closest('ol')!
      expect(grid.getAttribute('aria-busy')).toBe('true')
    })
  })
})

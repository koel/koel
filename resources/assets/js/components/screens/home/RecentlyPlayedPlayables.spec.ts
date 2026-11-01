import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import Router from '@/router'
import Component from './RecentlyPlayedPlayables.vue'

describe('recentlyPlayedPlayables.vue', () => {
  const h = createHarness()

  it('displays the songs', async () => {
    recentlyPlayedStore.excerptState.playables = h.factory('song').make({ play_count: 1 }, 6)
    h.render(Component)
    await waitFor(() => expect(screen.getAllByTestId('song-card')).toHaveLength(6))
  })

  it('shows a newly played song right away', async () => {
    recentlyPlayedStore.excerptState.playables = []
    recentlyPlayedStore.state.playables = h.factory('song').make(3)
    h.render(Component)

    await recentlyPlayedStore.add(h.factory('song').make({ play_count: 1 }))

    await waitFor(() => expect(screen.getAllByTestId('song-card')).toHaveLength(1))
  })

  it('hides deleted and unplayed songs', async () => {
    recentlyPlayedStore.excerptState.playables = [
      h.factory('song').make({ play_count: 1 }),
      h.factory('song').make({ play_count: 1, deleted: true }),
      h.factory('song').make({ play_count: 0 }),
    ]

    h.render(Component)

    await waitFor(() => expect(screen.getAllByTestId('song-card')).toHaveLength(1))
  })

  it('goes to dedicated screen', async () => {
    const mock = h.mock(Router, 'go')
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'View All' }))

    expect(mock).toHaveBeenCalledWith('/#/recently-played')
  })
})

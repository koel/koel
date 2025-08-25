import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { overviewStore } from '@/stores/overviewStore'
import Router from '@/router'
import Component from './RecentlyPlayedPlayables.vue'

describe('recentlyPlayedPlayables.vue', () => {
  const h = createHarness()

  it('displays the songs', () => {
    overviewStore.state.recentlyPlayed = h.factory('song', 6)
    expect(h.render(Component, {
      global: {
        stubs: {
          PlayableCard: h.stub('song-card'),
        },
      },
    }).getAllByTestId('song-card')).toHaveLength(6)
  })

  it('goes to dedicated screen', async () => {
    const mock = h.mock(Router, 'go')
    h.render(Component)

    await h.user.click(screen.getByRole('button', { name: 'View All' }))

    expect(mock).toHaveBeenCalledWith('/#/recently-played')
  })
})

import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { recentlyPlayedStore } from '@/stores/recentlyPlayedStore'
import Component from './RecentlyPlayedScreen.vue'

describe('recentlyPlayedScreen.vue', () => {
  const h = createHarness()

  const renderComponent = async (playables: Playable[] = []) => {
    recentlyPlayedStore.state.playables = playables
    const fetchMock = h.mock(recentlyPlayedStore, 'fetch')

    h.render(Component, {
      global: {
        stubs: {
          PlayableList: h.stub('song-list'),
        },
      },
    })

    h.visit('/recently-played')

    await waitFor(() => expect(fetchMock).toHaveBeenCalled())
  }

  it('displays the songs', async () => {
    await renderComponent(h.factory('song', 3))

    screen.getByTestId('song-list')
    expect(screen.queryByTestId('screen-empty-state')).toBeNull()
  })

  it('displays the empty state', async () => {
    await renderComponent()

    screen.getByTestId('screen-empty-state')
    expect(screen.queryByTestId('song-list')).toBeNull()
  })
})

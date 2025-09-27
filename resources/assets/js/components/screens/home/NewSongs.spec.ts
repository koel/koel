import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { overviewStore } from '@/stores/overviewStore'
import { screen, waitFor } from '@testing-library/vue'
import Component from './NewSongs.vue'

describe('newSongs.vue', () => {
  const h = createHarness()

  it('displays the songs', async () => {
    overviewStore.state.recentlyAddedSongs = h.factory('song', 6)
    h.render(Component)
    await waitFor(() => expect(screen.getAllByTestId('song-item')).toHaveLength(6))
  })
})

import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { overviewStore } from '@/stores/overviewStore'
import { screen, waitFor } from '@testing-library/vue'
import Component from './MostPlayedSongs.vue'

describe('mostPlayedSongs.vue', () => {
  const h = createHarness()

  it('displays the songs', async () => {
    overviewStore.state.mostPlayedSongs = h.factory('song', 6)
    h.render(Component)
    await waitFor(() => expect(screen.getAllByTestId('song-card')).toHaveLength(6))
  })
})

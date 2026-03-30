import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { overviewStore } from '@/stores/overviewStore'
import { screen, waitFor } from '@testing-library/vue'
import Component from './NewArtists.vue'

describe('newArtists.vue', () => {
  const h = createHarness()

  it('displays the artists', async () => {
    overviewStore.state.recentlyAddedArtists = h.factory('artist', 4)
    h.render(Component)
    await waitFor(() => expect(screen.getAllByTestId('artist-album-card')).toHaveLength(4))
  })
})

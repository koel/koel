import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { overviewStore } from '@/stores/overviewStore'
import TopArtists from './TopArtists.vue'

describe('topArtists.vue', () => {
  const h = createHarness()

  it('displays the artists', () => {
    overviewStore.state.mostPlayedArtists = h.factory('artist', 6)
    expect(h.render(TopArtists, {
      global: {
        stubs: {
          ArtistCard: h.stub('artist-card'),
        },
      },
    }).getAllByTestId('artist-card')).toHaveLength(6)
  })
})

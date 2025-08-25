import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { overviewStore } from '@/stores/overviewStore'
import TopAlbums from './TopAlbums.vue'

describe('topAlbums.vue', () => {
  const h = createHarness()

  it('displays the albums', () => {
    overviewStore.state.mostPlayedAlbums = h.factory('album', 6)
    expect(h.render(TopAlbums, {
      global: {
        stubs: {
          AlbumCard: h.stub('album-card'),
        },
      },
    }).getAllByTestId('album-card')).toHaveLength(6)
  })
})

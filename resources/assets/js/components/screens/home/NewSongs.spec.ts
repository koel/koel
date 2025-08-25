import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { overviewStore } from '@/stores/overviewStore'
import Component from './NewSongs.vue'

describe('newSongs.vue', () => {
  const h = createHarness()

  it('displays the songs', () => {
    overviewStore.state.recentlyAddedSongs = h.factory('song', 6)
    expect(h.render(Component, {
      global: {
        stubs: {
          SongCard: h.stub('song-card'),
        },
      },
    }).getAllByTestId('song-card')).toHaveLength(6)
  })
})

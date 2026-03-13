import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { overviewStore } from '@/stores/overviewStore'
import Component from './NewAlbums.vue'

describe('newAlbums.vue', () => {
  const h = createHarness()

  it('displays the albums', () => {
    overviewStore.state.recentlyAddedAlbums = h.factory('album', 6)
    expect(
      h
        .render(Component, {
          global: {
            stubs: {
              AlbumCard: h.stub('album-card'),
            },
          },
        })
        .getAllByTestId('album-card'),
    ).toHaveLength(6)
  })
})

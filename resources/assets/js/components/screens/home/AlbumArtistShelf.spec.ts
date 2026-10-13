import { screen } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { overviewStore } from '@/stores/overviewStore'
import Component from './AlbumArtistShelf.vue'

describe('albumArtistShelf.vue', () => {
  const h = createHarness()

  const renderComponent = (props: Record<string, unknown>) =>
    h.render(Component, {
      props: { header: 'Shelf', emptyText: 'Nothing here.', ...props },
      global: {
        stubs: {
          AlbumCard: h.stub('album-card'),
          ArtistCard: h.stub('artist-card'),
        },
      },
    })

  it('displays the albums', () => {
    overviewStore.state.mostPlayedAlbums = h.factory('album').make(6)
    renderComponent({ stateKey: 'mostPlayedAlbums' })

    expect(screen.getAllByTestId('album-card')).toHaveLength(6)
    expect(screen.queryByTestId('artist-card')).toBeNull()
  })

  it('displays the artists', () => {
    overviewStore.state.recentlyAddedArtists = h.factory('artist').make(4)
    renderComponent({ stateKey: 'recentlyAddedArtists' })

    expect(screen.getAllByTestId('artist-card')).toHaveLength(4)
    expect(screen.queryByTestId('album-card')).toBeNull()
  })

  it('hides the cards while loading', () => {
    overviewStore.state.mostPlayedArtists = h.factory('artist').make(4)
    renderComponent({ stateKey: 'mostPlayedArtists', loading: true })

    expect(screen.queryByTestId('artist-card')).toBeNull()
  })

  it('refreshes the shelf when refreshable', async () => {
    overviewStore.state.randomAlbums = h.factory('album').make(6)
    const refreshMock = h.mock(overviewStore, 'refreshRandomAlbums')
    renderComponent({ stateKey: 'randomAlbums', refresh: () => overviewStore.refreshRandomAlbums() })

    await h.user.click(screen.getByRole('button', { name: 'Refresh' }))

    expect(refreshMock).toHaveBeenCalled()
  })

  it('has no refresh button when not refreshable', () => {
    overviewStore.state.mostPlayedAlbums = h.factory('album').make(6)
    renderComponent({ stateKey: 'mostPlayedAlbums' })

    expect(screen.queryByRole('button', { name: 'Refresh' })).toBeNull()
  })
})

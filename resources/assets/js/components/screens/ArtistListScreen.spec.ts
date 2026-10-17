import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore } from '@/stores/preferenceStore'
import Component from './ArtistListScreen.vue'

const artistGridStub = {
  template: '<div data-testid="artist-grid"><div v-for="(a, i) in artists" :key="i" data-testid="artist-card" /></div>',
  props: ['artists'],
  methods: { scrollToTop() {} },
}

const artistTableStub = {
  template: '<div data-testid="artist-table-stub" />',
  props: ['artists', 'field', 'order'],
}

describe('artistListScreen.vue', () => {
  const h = createHarness()

  const renderComponent = async () => {
    const artists = h.factory('artist').make(9)
    const paginateMock = h.mock(artistStore, 'paginate').mockResolvedValueOnce('next-cursor-token')

    artistStore.state.artists = artists

    const rendered = h.render(Component, {
      global: {
        stubs: {
          ArtistGrid: artistGridStub,
          ArtistTable: artistTableStub,
        },
      },
    })

    await h.tick(2)

    return {
      ...rendered,
      paginateMock,
    }
  }

  it('renders', async () => {
    await renderComponent()
    expect(screen.getAllByTestId('artist-card')).toHaveLength(9)
  })

  it('shows a message when the library is empty', async () => {
    commonStore.state.song_length = 0

    await renderComponent()

    await waitFor(() => screen.getByTestId('screen-empty-state'))
  })

  it('renders the table when the view mode is table', async () => {
    preferenceStore.temporary.artists_view_mode = 'table'
    await renderComponent()

    expect(screen.queryByTestId('artist-grid')).toBeNull()
    screen.getByTestId('artist-table-stub')
  })

  it('switches between grid and table via the view mode toggle', async () => {
    preferenceStore.temporary.artists_view_mode = 'grid'
    await renderComponent()

    screen.getByTestId('artist-grid')
    expect(screen.queryByTestId('artist-table-stub')).toBeNull()

    await h.user.click(screen.getByRole('radio', { name: 'View as table' }))
    await waitFor(() => {
      screen.getByTestId('artist-table-stub')
      expect(screen.queryByTestId('artist-grid')).toBeNull()
    })

    await h.user.click(screen.getByRole('radio', { name: 'View as grid' }))
    await waitFor(() => {
      screen.getByTestId('artist-grid')
      expect(screen.queryByTestId('artist-table-stub')).toBeNull()
    })
  })

  it('shows all or only favorites upon toggling the button', async () => {
    const { paginateMock } = await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Favorites only' }))

    await waitFor(() =>
      expect(paginateMock).toHaveBeenNthCalledWith(2, {
        favorites_only: true,
        cursor: '',
        order: 'asc',
        sort: 'name',
      }),
    )

    await h.user.click(screen.getByRole('button', { name: 'Favorites only' }))

    await waitFor(() =>
      expect(paginateMock).toHaveBeenNthCalledWith(3, {
        favorites_only: false,
        cursor: '',
        order: 'asc',
        sort: 'name',
      }),
    )
  })

  it('filters out unfavorited artists in favorites mode', async () => {
    const artists = h.factory('artist').make({ favorite: true }, 5)
    artistStore.state.artists = artists

    h.mock(artistStore, 'paginate').mockResolvedValue(null)

    h.render(Component, {
      global: {
        stubs: {
          ArtistGrid: artistGridStub,
          ArtistTable: artistTableStub,
        },
      },
    })

    await h.tick(2)

    preferenceStore.artists_favorites_only = true
    await h.tick()

    expect(screen.getAllByTestId('artist-card')).toHaveLength(5)

    artistStore.state.artists[0].favorite = false
    await h.tick()

    expect(screen.getAllByTestId('artist-card')).toHaveLength(4)
  })

  it('shows empty state when no favorite artists', async () => {
    artistStore.state.artists = []

    h.mock(artistStore, 'paginate').mockResolvedValue(null)
    preferenceStore.artists_favorites_only = true

    h.render(Component, {
      global: {
        stubs: {
          ArtistGrid: artistGridStub,
          ArtistTable: artistTableStub,
        },
      },
    })

    await h.tick(2)

    await waitFor(() => {
      const emptyState = screen.getByTestId('screen-empty-state')
      expect(emptyState.textContent).toContain('No favorite artists')
    })
  })

  it('shows the server matches while filtering', async () => {
    preferenceStore.temporary.artists_favorites_only = false
    const matches = h.factory('artist').make(2)
    const searchMock = h.mock(artistStore, 'search').mockResolvedValue(matches)
    await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Search' }))
    await h.user.type(screen.getByPlaceholderText('Keywords'), 'foo')

    await waitFor(() => expect(searchMock).toHaveBeenCalledWith('foo', false))
    await waitFor(() => expect(screen.getAllByTestId('artist-card')).toHaveLength(2))
  })

  it('shows loading skeletons while a search runs', async () => {
    preferenceStore.temporary.artists_favorites_only = false
    h.mock(artistStore, 'search').mockReturnValue(new Promise(() => {}))
    await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Search' }))
    await h.user.type(screen.getByPlaceholderText('Keywords'), 'foo')

    await waitFor(() => screen.getByRole('status', { name: 'Loading' }))
  })
})

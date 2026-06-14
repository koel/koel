import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import Component from './AlbumListScreen.vue'

const albumGridStub = {
  template: '<div data-testid="album-grid"><div v-for="(a, i) in albums" :key="i" data-testid="album-card" /></div>',
  props: ['albums', 'showReleaseYear'],
  methods: { scrollToTop() {} },
}

const albumTableStub = {
  template: '<div data-testid="album-table" />',
  props: ['albums', 'field', 'order'],
}

describe('albumListScreen.vue', () => {
  const h = createHarness()

  const renderComponent = async () => {
    const paginateMock = h.mock(albumStore, 'paginate').mockResolvedValueOnce('next-cursor-token')
    albumStore.state.albums = h.factory('album').make(9)

    const rendered = h.render(Component, {
      global: {
        stubs: {
          AlbumGrid: albumGridStub,
          AlbumTable: albumTableStub,
        },
      },
    })

    await h.tick(2)

    return {
      rendered,
      paginateMock,
    }
  }

  it('renders', async () => {
    await renderComponent()
    expect(screen.getAllByTestId('album-card')).toHaveLength(9)
  })

  it('shows a message when the library is empty', async () => {
    commonStore.state.song_length = 0
    await renderComponent()

    await waitFor(() => screen.getByTestId('screen-empty-state'))
  })

  it('renders the table when the view mode is table', async () => {
    preferences.temporary.albums_view_mode = 'table'
    await renderComponent()

    expect(screen.queryByTestId('album-grid')).toBeNull()
    screen.getByTestId('album-table')
  })

  it('switches between grid and table via the view mode toggle', async () => {
    preferences.temporary.albums_view_mode = 'grid'
    await renderComponent()

    screen.getByTestId('album-grid')
    expect(screen.queryByTestId('album-table')).toBeNull()

    await h.user.click(screen.getByRole('radio', { name: 'View as table' }))
    await waitFor(() => {
      screen.getByTestId('album-table')
      expect(screen.queryByTestId('album-grid')).toBeNull()
    })

    await h.user.click(screen.getByRole('radio', { name: 'View as grid' }))
    await waitFor(() => {
      screen.getByTestId('album-grid')
      expect(screen.queryByTestId('album-table')).toBeNull()
    })
  })

  it('shows all or only favorites upon toggling the button', async () => {
    const { paginateMock } = await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Show favorites only' }))

    await waitFor(() =>
      expect(paginateMock).toHaveBeenNthCalledWith(2, {
        favorites_only: true,
        cursor: '',
        order: 'asc',
        sort: 'name',
      }),
    )

    await h.user.click(screen.getByRole('button', { name: 'Show all' }))

    await waitFor(() =>
      expect(paginateMock).toHaveBeenNthCalledWith(3, {
        favorites_only: false,
        cursor: '',
        order: 'asc',
        sort: 'name',
      }),
    )
  })

  it('filters out unfavorited albums in favorites mode', async () => {
    const albums = h.factory('album').make({ favorite: true }, 5)
    albumStore.state.albums = albums

    h.mock(albumStore, 'paginate').mockResolvedValue(null)

    h.render(Component, {
      global: {
        stubs: {
          AlbumGrid: albumGridStub,
          AlbumTable: albumTableStub,
        },
      },
    })

    await h.tick(2)

    preferences.albums_favorites_only = true
    await h.tick()

    expect(screen.getAllByTestId('album-card')).toHaveLength(5)

    albumStore.state.albums[0].favorite = false
    await h.tick()

    expect(screen.getAllByTestId('album-card')).toHaveLength(4)
  })

  it('shows empty state when no favorite albums', async () => {
    albumStore.state.albums = []

    h.mock(albumStore, 'paginate').mockResolvedValue(null)
    preferences.albums_favorites_only = true

    h.render(Component, {
      global: {
        stubs: {
          AlbumGrid: albumGridStub,
          AlbumTable: albumTableStub,
        },
      },
    })

    await h.tick(2)

    await waitFor(() => {
      const emptyState = screen.getByTestId('screen-empty-state')
      expect(emptyState.textContent).toContain('No favorite albums')
    })
  })
})

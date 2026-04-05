import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vite-plus/test'
import { createHarness } from '@/__tests__/TestHarness'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import Component from './AlbumListScreen.vue'

const virtualGridStub = {
  template: '<div data-testid="album-grid"><slot v-for="(item, i) in items" :key="i" :item="item" /></div>',
  props: ['items', 'minItemWidth'],
  methods: { scrollToTop() {} },
}

const albumCardStub = {
  template: '<div data-testid="album-card" :data-layout="layout" />',
  props: ['album', 'layout', 'showReleaseYear'],
}

describe('albumListScreen.vue', () => {
  const h = createHarness()

  const renderComponent = async () => {
    const paginator: PaginatorResource<Album> = {
      data: h.factory('album', 9),
      links: {
        next: '?page=1',
      },
      meta: {
        current_page: 0,
      },
    }

    const paginateMock = h.mock(albumStore, 'paginate').mockResolvedValueOnce(paginator)
    albumStore.state.albums = h.factory('album', 9)

    const rendered = h.render(Component, {
      global: {
        stubs: {
          AlbumCard: albumCardStub,
          VirtualGridScroller: virtualGridStub,
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

  it.each<[ViewMode, CardLayout]>([
    ['thumbnails', 'full'],
    ['list', 'compact'],
  ])('passes correct card layout for %s view mode', async (mode, expectedLayout) => {
    preferences.temporary.albums_view_mode = mode
    await renderComponent()

    const cards = screen.getAllByTestId('album-card')
    cards.forEach((card: HTMLElement) => expect(card.dataset.layout).toBe(expectedLayout))
  })

  it('switches layout via view mode toggle', async () => {
    await renderComponent()

    await h.user.click(screen.getByRole('radio', { name: 'View as list' }))
    await waitFor(() => {
      screen.getAllByTestId('album-card').forEach((card: HTMLElement) => expect(card.dataset.layout).toBe('compact'))
    })

    await h.user.click(screen.getByRole('radio', { name: 'View as thumbnails' }))
    await waitFor(() => {
      screen.getAllByTestId('album-card').forEach((card: HTMLElement) => expect(card.dataset.layout).toBe('full'))
    })
  })

  it('shows all or only favorites upon toggling the button', async () => {
    const { paginateMock } = await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Show favorites only' }))

    await waitFor(() =>
      expect(paginateMock).toHaveBeenNthCalledWith(2, {
        favorites_only: true,
        page: 1,
        order: 'asc',
        sort: 'name',
      }),
    )

    await h.user.click(screen.getByRole('button', { name: 'Show all' }))

    await waitFor(() =>
      expect(paginateMock).toHaveBeenNthCalledWith(3, {
        favorites_only: false,
        page: 1,
        order: 'asc',
        sort: 'name',
      }),
    )
  })

  it('filters out unfavorited albums in favorites mode', async () => {
    const albums = h.factory('album', 5, { favorite: true })
    albumStore.state.albums = albums

    h.mock(albumStore, 'paginate').mockResolvedValue(null)

    h.render(Component, {
      global: {
        stubs: {
          AlbumCard: albumCardStub,
          VirtualGridScroller: virtualGridStub,
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
          AlbumCard: albumCardStub,
          VirtualGridScroller: virtualGridStub,
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

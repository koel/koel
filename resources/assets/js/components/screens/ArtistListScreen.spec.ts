import { describe, expect, it } from 'vite-plus/test'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore } from '@/stores/preferenceStore'
import Component from './ArtistListScreen.vue'

const virtualGridStub = {
  template: '<div data-testid="artist-list"><slot v-for="(item, i) in items" :key="i" :item="item" /></div>',
  props: ['items', 'minItemWidth'],
  methods: { scrollToTop() {} },
}

const artistCardStub = {
  template: '<div data-testid="artist-card" :data-layout="layout" />',
  props: ['artist', 'layout'],
}

describe('artistListScreen.vue', () => {
  const h = createHarness()

  const renderComponent = async () => {
    const paginator: PaginatorResource<Artist> = {
      data: h.factory('artist', 9),
      links: {
        next: '?page=1',
      },
      meta: {
        current_page: 0,
      },
    }

    const paginateMock = h.mock(artistStore, 'paginate').mockResolvedValueOnce(paginator)

    artistStore.state.artists = paginator.data

    const rendered = h.render(Component, {
      global: {
        stubs: {
          ArtistCard: artistCardStub,
          VirtualGridScroller: virtualGridStub,
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

  it.each<[ViewMode, CardLayout]>([
    ['thumbnails', 'full'],
    ['list', 'compact'],
  ])('passes correct card layout for %s view mode', async (mode, expectedLayout) => {
    preferenceStore.temporary.artists_view_mode = mode
    await renderComponent()

    const cards = screen.getAllByTestId('artist-card')
    cards.forEach((card: HTMLElement) => expect(card.dataset.layout).toBe(expectedLayout))
  })

  it('switches layout via view mode toggle', async () => {
    await renderComponent()

    await h.user.click(screen.getByRole('radio', { name: 'View as list' }))
    await waitFor(() => {
      screen.getAllByTestId('artist-card').forEach((card: HTMLElement) => expect(card.dataset.layout).toBe('compact'))
    })

    await h.user.click(screen.getByRole('radio', { name: 'View as thumbnails' }))
    await waitFor(() => {
      screen.getAllByTestId('artist-card').forEach((card: HTMLElement) => expect(card.dataset.layout).toBe('full'))
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

  it('filters out unfavorited artists in favorites mode', async () => {
    const artists = h.factory('artist', 5, { favorite: true })
    artistStore.state.artists = artists

    h.mock(artistStore, 'paginate').mockResolvedValue(null)

    h.render(Component, {
      global: {
        stubs: {
          ArtistCard: artistCardStub,
          VirtualGridScroller: virtualGridStub,
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
          ArtistCard: artistCardStub,
          VirtualGridScroller: virtualGridStub,
        },
      },
    })

    await h.tick(2)

    await waitFor(() => {
      const emptyState = screen.getByTestId('screen-empty-state')
      expect(emptyState.textContent).toContain('No favorite artists')
    })
  })
})

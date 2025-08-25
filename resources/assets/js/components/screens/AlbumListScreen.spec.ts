import { screen, waitFor } from '@testing-library/vue'
import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { albumStore } from '@/stores/albumStore'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore as preferences } from '@/stores/preferenceStore'
import Component from './AlbumListScreen.vue'

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
          AlbumCard: h.stub('album-card'),
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

  it.each<[ViewMode]>([['list'], ['thumbnails']])('sets layout from preferences', async mode => {
    preferences.temporary.albums_view_mode = mode

    await renderComponent()

    await waitFor(() => expect(screen.getByTestId('album-grid').classList.contains(`as-${mode}`)).toBe(true))
  })

  it('switches layout', async () => {
    await renderComponent()

    await h.user.click(screen.getByRole('radio', { name: 'View as list' }))
    await waitFor(() => expect(screen.getByTestId('album-grid').classList.contains(`as-list`)).toBe(true))

    await h.user.click(screen.getByRole('radio', { name: 'View as thumbnails' }))
    await waitFor(() => expect(screen.getByTestId('album-grid').classList.contains(`as-thumbnails`)).toBe(true))
  })

  it('shows all or only favorites upon toggling the button', async () => {
    const { paginateMock } = await renderComponent()

    await h.user.click(screen.getByRole('button', { name: 'Show favorites only' }))

    await waitFor(() => expect(paginateMock).toHaveBeenNthCalledWith(2, {
      favorites_only: true,
      page: 1,
      order: 'asc',
      sort: 'name',
    }))

    await h.user.click(screen.getByRole('button', { name: 'Show all' }))

    await waitFor(() => expect(paginateMock).toHaveBeenNthCalledWith(3, {
      favorites_only: false,
      page: 1,
      order: 'asc',
      sort: 'name',
    }))
  })
})

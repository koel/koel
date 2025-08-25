import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { artistStore } from '@/stores/artistStore'
import { commonStore } from '@/stores/commonStore'
import { preferenceStore } from '@/stores/preferenceStore'
import Component from './ArtistListScreen.vue'

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
          ArtistCard: h.stub('artist-card'),
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

  it.each<[ViewMode]>([['list'], ['thumbnails']])('sets layout:%s from preferences', async mode => {
    preferenceStore.artists_view_mode = mode

    await renderComponent()

    await waitFor(() => expect(screen.getByTestId('artist-list').classList.contains(`as-${mode}`)).toBe(true))
  })

  it('switches layout', async () => {
    await renderComponent()

    await h.user.click(screen.getByRole('radio', { name: 'View as list' }))
    await waitFor(() => expect(screen.getByTestId('artist-list').classList.contains(`as-list`)).toBe(true))

    await h.user.click(screen.getByRole('radio', { name: 'View as thumbnails' }))
    await waitFor(() => expect(screen.getByTestId('artist-list').classList.contains(`as-thumbnails`)).toBe(true))
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

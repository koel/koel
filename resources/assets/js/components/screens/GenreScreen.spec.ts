import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { genreStore } from '@/stores/genreStore'
import { playableStore } from '@/stores/playableStore'
import Component from './GenreScreen.vue'
import { eventBus } from '@/utils/eventBus'

describe('genreScreen', () => {
  const h = createHarness()

  const renderComponent = async (genre?: Genre, songs?: Song[]) => {
    genre = genre || h.factory('genre')

    const fetchGenreMock = h.mock(genreStore, 'fetchOne').mockResolvedValue(genre)
    const paginateMock = h.mock(playableStore, 'paginateSongsByGenre').mockResolvedValue({
      nextPage: 2,
      songs: songs || h.factory('song', 13),
    })

    await h.router.activateRoute({
      path: `genres/${genre.id}`,
      screen: 'Genre',
    }, { id: genre.id })

    const rendered = h.render(Component, {
      global: {
        stubs: {
          SongList: h.stub('song-list'),
        },
      },
    })

    await waitFor(() => {
      expect(fetchGenreMock).toHaveBeenCalledWith(genre!.id)
      expect(paginateMock).toHaveBeenCalledWith(genre!.id, {
        sort: 'title',
        order: 'asc',
        page: 1,
      })
    })

    await h.tick(2)

    return {
      ...rendered,
      genre,
    }
  }

  it('renders the song list', async () => {
    await renderComponent()
    expect(screen.getByTestId('song-list')).toBeTruthy()
  })

  it('requests Actions menu', async () => {
    const { genre } = await renderComponent()
    const emitMock = h.mock(eventBus, 'emit')

    await waitFor(async () => {
      await h.user.click(screen.getByRole('button', { name: 'More Actions' }))
      expect(emitMock).toHaveBeenCalledWith('GENRE_CONTEXT_MENU_REQUESTED', expect.any(MouseEvent), genre)
    })
  })
})

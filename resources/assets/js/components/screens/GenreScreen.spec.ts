import { describe, expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { genreStore } from '@/stores/genreStore'
import { playableStore } from '@/stores/playableStore'
import Component from './GenreScreen.vue'

describe('genreScreen', () => {
  const h = createHarness()

  const renderComponent = async (genre?: Genre, songs?: Song[]) => {
    genre = genre || h.factory('genre')

    const fetchGenreMock = h.mock(genreStore, 'fetchOne').mockResolvedValue(genre)
    const paginateMock = h.mock(playableStore, 'paginateSongsByGenre').mockResolvedValue({
      nextPage: 2,
      songs: songs || h.factory('song', 13),
    })

    const rendered = h.visit(`genres/${genre.id}`).render(Component, {
      global: {
        stubs: {
          SongList: h.stub('song-list'),
          ScreenBase: h.stub('screen-base'),
          ScreenHeader: h.stub('screen-header'),
          ScreenEmptyState: h.stub('screen-empty-state'),
          PlayableListSkeleton: h.stub('playable-list-skeleton'),
          ScreenHeaderSkeleton: h.stub('screen-header-skeleton'),
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
    // Wait for loading to complete and songs to be loaded
    await waitFor(() => {
      expect(screen.getByTestId('song-list')).toBeTruthy()
    }, { timeout: 3000 })
  })
})

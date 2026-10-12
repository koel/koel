import { describe, expect, it } from 'vite-plus/test'
import { defineComponent } from 'vue'
import { screen, waitFor } from '@testing-library/vue'
import { createHarness } from '@/__tests__/TestHarness'
import { genreStore } from '@/stores/genreStore'
import { playableStore } from '@/stores/playableStore'
import { usePageTitle } from '@/composables/usePageTitle'
import Component from './GenreScreen.vue'

describe('genreScreen', () => {
  const h = createHarness()

  const renderComponent = async (genre?: Genre, songs?: Song[]) => {
    genre = genre || h.factory('genre').make()

    const fetchGenreMock = h.mock(genreStore, 'fetchOne').mockResolvedValue(genre)
    const paginateMock = h.mock(playableStore, 'paginateSongsByGenre').mockResolvedValue({
      nextCursor: 'next-token',
      songs: songs || h.factory('song').make(13),
    })

    const rendered = h.visit(`genres/${genre.id}`).render(Component, {
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
        cursor: '',
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
    screen.getByTestId('song-list')
  })

  it('names a genre without a name "No Genre" in the page title', async () => {
    const DocumentTitle = defineComponent({ setup: () => usePageTitle().syncDocumentTitle(), template: '<div />' })
    h.render(DocumentTitle)

    await renderComponent(h.factory('genre').make({ name: '' }))

    expect(document.title).toBe('No Genre – Koel')
  })
})

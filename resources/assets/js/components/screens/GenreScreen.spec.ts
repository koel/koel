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

  it('does not fetch the genre again when loading more songs', async () => {
    const genre = h.factory('genre').make()
    const fetchGenreMock = h.mock(genreStore, 'fetchOne').mockResolvedValue(genre)
    const paginateMock = h.mock(playableStore, 'paginateSongsByGenre').mockResolvedValue({
      songs: h.factory('song').make(5),
      nextCursor: 'next-token',
    })

    h.visit(`genres/${genre.id}`).render(Component, {
      global: {
        stubs: {
          SongList: defineComponent({
            emits: ['scrolled-to-end'],
            template: '<button @click="$emit(\'scrolled-to-end\')">More</button>',
          }),
        },
      },
    })

    await waitFor(() => expect(paginateMock).toHaveBeenCalledOnce())
    await h.tick(2)

    await h.user.click(screen.getByRole('button', { name: 'More' }))

    await waitFor(() => expect(paginateMock).toHaveBeenCalledTimes(2))
    expect(fetchGenreMock).toHaveBeenCalledOnce()
  })

  it('fetches the new genre when switching genres', async () => {
    await renderComponent()
    const otherGenre = h.factory('genre').make()
    const fetchGenreMock = h.mock(genreStore, 'fetchOne').mockResolvedValue(otherGenre)

    h.visit(`genres/${otherGenre.id}`)

    await waitFor(() => expect(fetchGenreMock).toHaveBeenLastCalledWith(otherGenre.id))
  })

  it('ignores a slow response for a genre that is no longer shown', async () => {
    const DocumentTitle = defineComponent({ setup: () => usePageTitle().syncDocumentTitle(), template: '<div />' })
    h.render(DocumentTitle)
    await renderComponent()

    const slowGenre = h.factory('genre').make({ name: 'Slow' })
    const currentGenre = h.factory('genre').make({ name: 'Current' })
    let resolveSlowGenre: (genre: Genre) => void = () => {}

    h.mock(genreStore, 'fetchOne').mockImplementation(id =>
      id === slowGenre.id ? new Promise<Genre>(resolve => (resolveSlowGenre = resolve)) : Promise.resolve(currentGenre),
    )

    h.visit(`genres/${slowGenre.id}`)
    await h.tick(2)
    h.visit(`genres/${currentGenre.id}`)
    await waitFor(() => expect(document.title).toBe('Current – Koel'))

    resolveSlowGenre(slowGenre)
    await h.tick(2)

    expect(document.title).toBe('Current – Koel')
  })

  it('keeps a new sort made while a page is loading', async () => {
    const genre = h.factory('genre').make()
    let resolveStalePage: (page: { songs: Song[]; nextCursor: string | null }) => void = () => {}

    h.mock(genreStore, 'fetchOne').mockResolvedValue(genre)

    const paginateMock = h
      .mock(playableStore, 'paginateSongsByGenre')
      .mockResolvedValueOnce({ songs: h.factory('song').make(5), nextCursor: 'next-token' })
      .mockImplementationOnce(
        () =>
          new Promise(resolve => {
            resolveStalePage = resolve
          }),
      )
      .mockResolvedValueOnce({ songs: h.factory('song').make(5), nextCursor: null })

    h.visit(`genres/${genre.id}`).render(Component, {
      global: {
        stubs: {
          SongList: defineComponent({
            emits: ['sort', 'scrolled-to-end'],
            template: `
              <div data-testid="song-list">
                <button @click="$emit('scrolled-to-end')">More</button>
                <button @click="$emit('sort', 'artist_name', 'desc')">Sort</button>
              </div>
            `,
          }),
        },
      },
    })

    await waitFor(() => expect(paginateMock).toHaveBeenCalledOnce())
    await h.tick(2)

    await h.user.click(screen.getByRole('button', { name: 'More' }))
    await h.user.click(screen.getByRole('button', { name: 'Sort' }))
    resolveStalePage({ songs: h.factory('song').make(5), nextCursor: 'stale-cursor' })

    await waitFor(() => {
      expect(paginateMock).toHaveBeenCalledTimes(3)
      expect(paginateMock).toHaveBeenLastCalledWith(genre.id, { sort: 'artist_name', order: 'desc', cursor: '' })
    })
  })

  it('loads the songs of a genre opened while another one is still loading', async () => {
    const first = h.factory('genre').make()
    const second = h.factory('genre').make()
    let resolveFirst: ((page: { nextCursor: string | null; songs: Song[] }) => void) | undefined

    h.mock(genreStore, 'fetchOne').mockImplementation(async id => (id === first.id ? first : second))

    const paginateMock = h
      .mock(playableStore, 'paginateSongsByGenre')
      .mockImplementation(async id =>
        id === first.id
          ? new Promise(resolve => (resolveFirst = resolve))
          : { nextCursor: null, songs: h.factory('song').make(2) },
      )

    h.visit(`genres/${first.id}`).render(Component, { global: { stubs: { SongList: h.stub('song-list') } } })
    await waitFor(() => expect(resolveFirst).toBeDefined())

    h.visit(`genres/${second.id}`)
    resolveFirst!({ nextCursor: 'next-token', songs: h.factory('song').make(3) })

    await waitFor(() => expect(paginateMock).toHaveBeenLastCalledWith(second.id, expect.anything()))
  })
})

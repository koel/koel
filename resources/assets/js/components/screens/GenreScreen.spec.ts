import { expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { genreStore } from '@/stores/genreStore'
import { playableStore } from '@/stores/playableStore'
import Component from './GenreScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders the song list', async () => {
      await this.renderComponent()
      expect(screen.getByTestId('song-list')).toBeTruthy()
    })
  }

  private async renderComponent (genre?: Genre, songs?: Song[]) {
    genre = genre || factory('genre')

    const fetchGenreMock = this.mock(genreStore, 'fetchOne').mockResolvedValue(genre)
    const paginateMock = this.mock(playableStore, 'paginateSongsByGenre').mockResolvedValue({
      nextPage: 2,
      songs: songs || factory('song', 13),
    })

    await this.router.activateRoute({
      path: `genres/${genre.id}`,
      screen: 'Genre',
    }, { id: genre.id })

    const rendered = this.render(Component, {
      global: {
        stubs: {
          SongList: this.stub('song-list'),
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

    await this.tick(2)

    return rendered
  }
}

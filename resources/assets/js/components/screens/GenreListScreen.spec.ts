import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { genreStore } from '@/stores'
import GenreListScreen from './GenreListScreen.vue'
import { waitFor } from '@testing-library/vue'

new class extends UnitTestCase {
  protected test () {
    it('renders the list of genres and their song counts', async () => {
      // ensure there's no duplicated names
      const genres = [
        factory<Genre>('genre', { name: 'Rock', song_count: 10 }),
        factory<Genre>('genre', { name: 'Pop', song_count: 20 }),
        factory<Genre>('genre', { name: 'Jazz', song_count: 30 })
      ]

      const fetchMock = this.mock(genreStore, 'fetchAll').mockResolvedValue(genres)

      const { getByTitle } = this.render(GenreListScreen)

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalled()
        genres.forEach(genre => getByTitle(`${genre.name}: ${genre.song_count} songs`))
      })
    })
  }
}

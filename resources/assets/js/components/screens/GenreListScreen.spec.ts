import { expect, it } from 'vitest'
import { screen, waitFor } from '@testing-library/vue'
import UnitTestCase from '@/__tests__/UnitTestCase'
import factory from '@/__tests__/factory'
import { commonStore } from '@/stores/commonStore'
import { genreStore } from '@/stores/genreStore'
import GenreListScreen from './GenreListScreen.vue'

new class extends UnitTestCase {
  protected test () {
    it('renders the list of genres and their song counts', async () => {
      // ensure there's no duplicated names
      const genres = [
        factory('genre', { name: 'Rock', song_count: 10 }),
        factory('genre', { name: 'Pop', song_count: 20 }),
        factory('genre', { name: 'Jazz', song_count: 30 }),
      ]

      const fetchMock = this.mock(genreStore, 'fetchAll').mockResolvedValue(genres)

      this.render(GenreListScreen)

      await waitFor(() => {
        expect(fetchMock).toHaveBeenCalled()
        genres.forEach(genre => screen.getByTitle(`${genre.name}: ${genre.song_count} songs`))
      })
    })

    it('shows a message when the library is empty', async () => {
      commonStore.state.song_length = 0
      const fetchMock = this.mock(genreStore, 'fetchAll')

      this.render(GenreListScreen)

      await waitFor(() => {
        expect(fetchMock).not.toHaveBeenCalled()
        screen.getByTestId('screen-empty-state')
      })
    })
  }
}

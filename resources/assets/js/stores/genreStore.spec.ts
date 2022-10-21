import { expect, it } from 'vitest'
import factory from '@/__tests__/factory'
import UnitTestCase from '@/__tests__/UnitTestCase'
import { http } from '@/services'
import { genreStore } from '@/stores/genreStore'

new class extends UnitTestCase {
  protected test () {
    it('fetches all genres', async () => {
      const genres = factory<Genre>('genre', 3)
      this.mock(http, 'get').mockResolvedValue(genres)

      expect(await genreStore.fetchAll()).toEqual(genres)
    })

    it('fetches a single genre', async () => {
      const genre = factory<Genre>('genre')
      this.mock(http, 'get').mockResolvedValue(genre)

      expect(await genreStore.fetchOne(genre.name)).toEqual(genre)
      expect(http.get).toHaveBeenCalledWith(`genres/${genre.name}`)
    })
  }
}

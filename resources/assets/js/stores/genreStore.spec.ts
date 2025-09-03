import { describe, expect, it } from 'vitest'
import { createHarness } from '@/__tests__/TestHarness'
import { http } from '@/services/http'
import { genreStore } from '@/stores/genreStore'

describe('genreStore', () => {
  const h = createHarness()

  it('fetches all genres', async () => {
    const genres = h.factory('genre', 3)
    h.mock(http, 'get').mockResolvedValue(genres)

    expect(await genreStore.fetchAll()).toEqual(genres)
  })

  it('fetches a single genre', async () => {
    const genre = h.factory('genre')
    h.mock(http, 'get').mockResolvedValue(genre)

    expect(await genreStore.fetchOne(genre.id)).toEqual(genre)
    expect(http.get).toHaveBeenCalledWith(`genres/${genre.id}`)
  })
})

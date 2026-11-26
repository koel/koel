import { describe, expect, it } from 'vite-plus/test'
import factory from '@/__tests__/factory'
import { getGenreSize, getLargestGenreSongCount } from './genreSize'

describe('getGenreSize', () => {
  it.each([
    [1, 0],
    [2, 1],
    [10, 2],
    [30, 3],
    [109, 4],
  ])('sizes a genre with %d songs as %d when the largest has 109', (songCount, expected) => {
    expect(getGenreSize(factory('genre').make({ name: 'Rock', song_count: songCount }), 109)).toBe(expected)
  })

  it('gives songs without a genre a fixed size', () => {
    expect(getGenreSize(factory('genre').make({ name: '', song_count: 1351 }), 109)).toBe(3)
  })

  it('keeps every genre small when no genre has more than one song', () => {
    expect(getGenreSize(factory('genre').make({ name: 'Rock', song_count: 1 }), 1)).toBe(0)
  })
})

describe('getLargestGenreSongCount', () => {
  it('ignores songs without a genre', () => {
    const genres = [
      factory('genre').make({ name: '', song_count: 1351 }),
      factory('genre').make({ name: 'Rock', song_count: 24 }),
      factory('genre').make({ name: 'Power Metal', song_count: 109 }),
    ]

    expect(getLargestGenreSongCount(genres)).toBe(109)
  })
})

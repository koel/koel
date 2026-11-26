export type GenreSize = 0 | 1 | 2 | 3 | 4

const NO_GENRE_SIZE: GenreSize = 3

export const getLargestGenreSongCount = (genres: Genre[]) =>
  Math.max(0, ...genres.filter(genre => genre.name).map(genre => genre.song_count))

export const getGenreSize = (genre: Genre, largestSongCount: number): GenreSize => {
  if (!genre.name) {
    return NO_GENRE_SIZE
  }

  if (genre.song_count <= 1 || largestSongCount <= 1) {
    return 0
  }

  const step = Math.floor((Math.log(genre.song_count) / Math.log(largestSongCount)) * 4)

  return (1 + Math.min(3, step)) as GenreSize
}

import { computed } from 'vue'
import { getDefaultCover, secondsToHis } from '@/utils'

export const useArtistAttributes = (artist: Artist) => {
  const length = computed(() => artist.songs.reduce((acc: number, song: Song) => acc + song.length, 0))
  const fmtLength = computed(() => secondsToHis(length.value))

  const image = computed(() => {
    if (!artist.image) {
      artist.image = getDefaultCover()

      artist.albums.every(album => {
        // If there's a "real" cover, use it.
        if (album.cover !== getDefaultCover()) {
          artist.image = album.cover
          return false
        }
      })
    }

    return artist.image
  })

  return {
    length,
    fmtLength,
    image
  }
}

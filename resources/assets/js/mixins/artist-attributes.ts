import { computed } from 'vue'
import { getDefaultCover, secondsToHis } from '@/utils'

export const useArtistAttributes = () => {
  const props = defineProps<{ artist: Artist }>()
  const length = computed(() => props.artist.songs.reduce((acc: number, song: Song) => acc + song.length, 0))
  const fmtLength = computed(() => secondsToHis(length.value))

  const image = computed(() => {
    if (!props.artist.image) {
      props.artist.image = getDefaultCover()

      props.artist.albums.every((album: Album) => {
        // If there's a "real" cover, use it.
        if (album.cover !== getDefaultCover()) {
          props.artist.image = album.cover
          return false
        }
      })
    }

    return props.artist.image
  })

  return {
    props,
    length,
    fmtLength,
    image
  }
}

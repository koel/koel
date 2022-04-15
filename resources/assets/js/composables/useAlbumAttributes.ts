import { computed } from 'vue'
import { secondsToHis } from '@/utils'

export const useAlbumAttributes = (album: Album) => {
  const length = computed(() => album.songs.reduce((acc: number, song: Song) => acc + song.length, 0))
  const fmtLength = computed(() => secondsToHis(length.value))

  return {
    length,
    fmtLength
  }
}

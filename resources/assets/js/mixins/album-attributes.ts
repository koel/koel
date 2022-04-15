import { computed } from 'vue'
import { secondsToHis } from '@/utils'

export const useAlbumAttributes = () => {
  const props = defineProps<{ album: Album }>()
  const length = computed(() => props.album.songs.reduce((acc: number, song: Song) => acc + song.length, 0))
  const fmtLength = computed(() => secondsToHis(length.value))

  return {
    props,
    length,
    fmtLength
  }
}

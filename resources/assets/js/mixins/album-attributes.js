import { secondsToHis } from '@/utils'

export default {
  computed: {
    length () {
      return this.album.songs.reduce((acc, song) => acc + song.length, 0)
    },

    fmtLength () {
      return secondsToHis(this.length)
    }
  }
}

import { secondsToHis } from '../utils'
import config from '../config'

export default {
  computed: {
    length () {
      return this.artist.songs.reduce((acc, song) => {
        return acc + song.length
      }, 0)
    },

    fmtLength () {
      return secondsToHis(this.length)
    },

    image () {
      if (!this.artist.image) {
        this.artist.image = config.unknownCover

        this.artist.albums.every(album => {
          // If there's a "real" cover, use it.
          if (album.image !== config.unknownCover) {
            this.artist.image = album.cover
            // I want to break free.
            return false
          }
        })
      }

      return this.artist.image
    }
  }
}

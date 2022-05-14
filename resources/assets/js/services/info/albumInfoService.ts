import { secondsToHis } from '@/utils'
import { httpService } from '@/services'

export const albumInfoService = {
  async fetch (album: Album) {
    if (!album.info) {
      const info = await httpService.get<AlbumInfo | null>(`album/${album.id}/info`)

      if (info) {
        this.merge(album, info)
      }
    }

    return album
  },

  /**
   * Merge the (fetched) info into an album.
   */
  merge: (album: Album, info: AlbumInfo) => {
    // Convert the duration into i:s
    if (info.tracks) {
      info.tracks.forEach(track => {
        track.fmtLength = secondsToHis(track.length)
      })
    }

    // If the album cover is not in a nice form, discard.
    if (typeof info.image !== 'string') {
      info.image = null
    }

    // Set the album cover on the client side to the retrieved image from server.
    if (info.image) {
      album.cover = info.image
    }

    album.info = info
  }
}

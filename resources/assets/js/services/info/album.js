import { each } from 'lodash'

import { secondsToHis } from '../../utils'
import { http } from '..'

export const albumInfo = {
  /**
   * Get extra album info (from Last.fm).
   *
   * @param  {Object}  album
   */
  fetch (album) {
    return new Promise((resolve, reject) => {
      if (album.info) {
        resolve(album)
        return
      }

      http.get(`album/${album.id}/info`, ({ data }) => {
        data && this.merge(album, data)
        resolve(album)
      }, error => reject(error))
    })
  },

  /**
   * Merge the (fetched) info into an album.
   *
   * @param  {Object} album
   * @param  {Object} info
   */
  merge (album, info) {
    // Convert the duration into i:s
    info.tracks && each(info.tracks, track => {
      track.fmtLength = secondsToHis(track.length)
    })

    // If the album cover is not in a nice form, discard.
    if (typeof info.image !== 'string') {
      info.image = null
    }

    // Set the album cover on the client side to the retrieved image from server.
    if (info.cover) {
      album.cover = info.cover
    }

    album.info = info
  }
}

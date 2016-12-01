import { http } from '..'

export const artistInfo = {
  /**
   * Get extra artist info (from Last.fm).
   *
   * @param  {Object}  artist
   */
  fetch (artist) {
    return new Promise((resolve, reject) => {
      if (artist.info) {
        resolve(artist)
        return
      }

      http.get(`artist/${artist.id}/info`, data => {
        data && this.merge(artist, data)
        resolve(artist)
      }, r => reject(r))
    })
  },

  /**
   * Merge the (fetched) info into an artist.
   *
   * @param  {Object} artist
   * @param  {Object} info
   */
  merge (artist, info) {
    // If the artist image is not in a nice form, discard.
    if (typeof info.image !== 'string') {
      info.image = null
    }

    // Set the artist image on the client side to the retrieved image from server.
    if (info.image) {
      artist.image = info.image
    }

    artist.info = info
  }
}

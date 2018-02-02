/*eslint-disable camelcase*/

import { http, albumInfo, artistInfo } from '..'

export const songInfo = {
  /**
   * Get extra song information (lyrics, artist info, album info, similar).
   *
   * @param  {Object}   song
   */
  fetch (song) {
    return new Promise((resolve, reject) => {
      if (song.infoRetrieved) {
        resolve(song)
        return
      }

      http.get(`${song.id}/info`, ({ data: { artist_info, album_info, youtube, lyrics, similar}}) => {
        song.lyrics = lyrics
        song.similar = similar
        artist_info && artistInfo.merge(song.artist, artist_info)
        album_info && albumInfo.merge(song.album, album_info)
        song.youtube = youtube
        song.infoRetrieved = true
        resolve(song)
      }, error => reject(error))
    })
  }
}

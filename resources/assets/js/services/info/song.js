/*eslint-disable camelcase*/

import { http, albumInfo, artistInfo } from '..'

export const songInfo = {
  /**
   * Get extra song information (lyrics, artist info, album info).
   *
   * @param  {Object}   song
   */
  fetch (song) {
    return new Promise((resolve, reject) => {
      // Check if the song's info has been retrieved before.
      if (song.infoRetrieved) {
        resolve(song)
        return
      }

      http.get(`${song.id}/info`, ({ data: { artist_info, album_info, youtube, lyrics }}) => {
        song.lyrics = lyrics
        artist_info && artistInfo.merge(song.artist, artist_info)
        album_info && albumInfo.merge(song.album, album_info)
        song.youtube = youtube
        song.infoRetrieved = true
        resolve(song)
      }, error => reject(error))
    })
  }
}

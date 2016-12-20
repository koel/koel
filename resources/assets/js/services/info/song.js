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

      http.get(`${song.id}/info`, response => {
        song.lyrics = response.data.lyrics
        response.data.artist_info && artistInfo.merge(song.artist, response.data.artist_info)
        response.data.album_info && albumInfo.merge(song.album, response.data.album_info)
        song.youtube = response.data.youtube
        song.infoRetrieved = true
        resolve(song)
      }, error => reject(error))
    })
  }
}

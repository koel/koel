import { http } from '.'
import { event } from '../utils'
import router from '../router'

export const youtube = {
  /**
   * Search for YouTube videos related to a song.
   *
   * @param  {Object}   song
   */
  searchVideosRelatedToSong (song) {
    if (!song.youtube) {
      song.youtube = {}
    }

    const pageToken = song.youtube.nextPageToken || ''
    return new Promise((resolve, reject) => {
      http.get(`youtube/search/song/${song.id}?pageToken=${pageToken}`, response => {
        song.youtube.nextPageToken = response.data.nextPageToken
        song.youtube.items.push(...response.data.items)
        resolve()
      }, error => reject(error))
    })
  },

  /**
   * Play a YouTube video.
   *
   * @param  {string} id The video ID
   */
  play (id) {
    event.emit('youtube:play', id)
    router.go('youtube')
  }
}

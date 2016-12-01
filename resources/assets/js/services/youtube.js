import { http } from '.'
import { event } from '../utils'
import router from '../router'

export const youtube = {
  /**
   * Search for YouTube videos related to a song.
   *
   * @param  {Object}   song
   * @param  {Function} cb
   */
  searchVideosRelatedToSong (song, cb = null) {
    if (!song.youtube) {
      song.youtube = {}
    }

    const pageToken = song.youtube.nextPageToken || ''
    http.get(`youtube/search/song/${song.id}?pageToken=${pageToken}`).then(data => {
      song.youtube.nextPageToken = data.nextPageToken
      song.youtube.items.push(...data.items)
      cb && cb()
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

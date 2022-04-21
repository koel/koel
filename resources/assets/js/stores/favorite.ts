import { difference, union } from 'lodash'
import { http } from '@/services'
import { arrayify } from '@/utils'
import { reactive } from 'vue'

export const favoriteStore = {
  state: reactive({
    songs: [] as Song[],
    length: 0,
    fmtLength: ''
  }),

  get all () {
    return this.state.songs
  },

  set all (value: Song[]) {
    this.state.songs = value
  },

  async toggleOne (song: Song) {
    // Don't wait for the HTTP response to update the status, just toggle right away.
    // This may cause a minor problem if the request fails somehow, but do we care?
    song.liked = !song.liked
    song.liked ? this.add(song) : this.remove(song)

    await http.post<Song>('interaction/like', { song: song.id })
  },

  add (songs: Song | Song[]) {
    this.all = union(this.all, arrayify(songs))
  },

  remove (songs: Song | Song[]) {
    this.all = difference(this.all, arrayify(songs))
  },

  clear (): void {
    this.all = []
  },

  async like (songs: Song[]) {
    // Don't wait for the HTTP response to update the status, just set them to Liked right away.
    // This may cause a minor problem if the request fails somehow, but do we care?
    songs.forEach(song => { song.liked = true })
    this.add(songs)

    await http.post('interaction/batch/like', { songs: songs.map(song => song.id) })
  },

  async unlike (songs: Song[]) {
    songs.forEach(song => { song.liked = false })
    this.remove(songs)

    await http.post('interaction/batch/unlike', { songs: songs.map(song => song.id) })
  }
}

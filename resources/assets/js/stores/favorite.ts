import { difference, union } from 'lodash'
import { http } from '@/services'

export const favoriteStore = {
  state: {
    songs: [] as Song[],
    length: 0,
    fmtLength: ''
  },

  get all (): Song[] {
    return this.state.songs
  },

  set all (value: Song[]) {
    this.state.songs = value
  },

  async toggleOne (song: Song): Promise<void> {
    // Don't wait for the HTTP response to update the status, just toggle right away.
    // This may cause a minor problem if the request fails somehow, but do we care?
    song.liked = !song.liked
    song.liked ? this.add(song) : this.remove(song)

    await http.post<Song>('interaction/like', { song: song.id })
  },

  /**
   * Add a song/songs into the store.
   */
  add (songs: Song | Song[]): void {
    this.all = union(this.all, (<Song[]>[]).concat(songs))
  },

  /**
   * Remove a song/songs from the store.
   */
  remove (songs: Song | Song[]): void {
    this.all = difference(this.all, (<Song[]>[]).concat(songs))
  },

  clear (): void {
    this.all = []
  },

  async like (songs: Song[]): Promise<void> {
    // Don't wait for the HTTP response to update the status, just set them to Liked right away.
    // This may cause a minor problem if the request fails somehow, but do we care?
    songs.forEach(song => { song.liked = true })
    this.add(songs)

    await http.post('interaction/batch/like', { songs: songs.map(song => song.id) })
  },

  async unlike (songs: Song[]): Promise<void> {
    songs.forEach(song => { song.liked = false })
    this.remove(songs)

    await http.post('interaction/batch/unlike', { songs: songs.map(song => song.id) })
  }
}

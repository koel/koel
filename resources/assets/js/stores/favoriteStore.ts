import { reactive } from 'vue'
import { differenceBy, unionBy } from 'lodash'
import { http } from '@/services'
import { arrayify } from '@/utils'
import { songStore } from '@/stores'

export const favoriteStore = {
  state: reactive<{ playables: Playable[] }>({
    playables: [],
  }),

  async toggleOne (playable: Playable) {
    // Don't wait for the HTTP response to update the status, just toggle right away.
    // This may cause a minor problem if the request fails somehow, but do we care?
    playable.liked = !playable.liked
    playable.liked ? this.add(playable) : this.remove(playable)

    await http.post<Playable>('interaction/like', { song: playable.id })
  },

  add (songs: MaybeArray<Playable>) {
    this.state.playables = unionBy(this.state.playables, arrayify(songs), 'id')
  },

  remove (songs: MaybeArray<Playable>) {
    this.state.playables = differenceBy(this.state.playables, arrayify(songs), 'id')
  },

  async like (songs: Playable[]) {
    // Don't wait for the HTTP response to update the status, just set them to Liked right away.
    // This may cause a minor problem if the request fails somehow, but do we care?
    songs.forEach(song => (song.liked = true))
    this.add(songs)

    await http.post('interaction/batch/like', { songs: songs.map(song => song.id) })
  },

  async unlike (songs: Playable[]) {
    songs.forEach(song => (song.liked = false))
    this.remove(songs)

    await http.post('interaction/batch/unlike', { songs: songs.map(song => song.id) })
  },

  async fetch () {
    this.state.playables = songStore.syncWithVault(await http.get<Playable[]>('songs/favorite'))
    return this.state.playables
  },
}

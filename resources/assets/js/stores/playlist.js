import { each, find, map, difference, union } from 'lodash'
import NProgress from 'nprogress'

import stub from '../stubs/playlist'
import { http } from '../services'
import { alerts, pluralize } from '../utils'
import { songStore } from '.'

export const playlistStore = {
  stub,

  state: {
    playlists: []
  },

  init (playlists) {
    this.all = playlists
    each(this.all, this.objectifySongs)
  },

  /**
   * All playlists of the current user.
   *
   * @return {Array.<Object>}
   */
  get all () {
    return this.state.playlists
  },

  /**
   * Set all playlists.
   *
   * @param  {Array.<Object>} value
   */
  set all (value) {
    this.state.playlists = value
  },

  /**
   * Find a playlist by its ID
   *
   * @param  {Number} id
   *
   * @return {Object}
   */
  byId (id) {
    return find(this.all, { id })
  },

  /**
   * Objectify all songs in the playlist.
   * (Initially, a playlist only contain the song IDs).
   *
   * @param  {Object} playlist
   */
  objectifySongs (playlist) {
    playlist.songs = songStore.byIds(playlist.songs)
  },

  /**
   * Get all songs in a playlist.
   *
   * @param {Object}
   *
   * return {Array.<Object>}
   */
  getSongs (playlist) {
    return playlist.songs
  },

  /**
   * Add a playlist/playlists into the store.
   *
   * @param {Array.<Object>|Object} playlists
   */
  add (playlists) {
    this.all = union(this.all, [].concat(playlists))
  },

  /**
   * Remove a playlist/playlists from the store.
   *
   * @param  {Array.<Object>|Object} playlist
   */
  remove (playlists) {
    this.all = difference(this.all, [].concat(playlists))
  },

  /**
   * Create a new playlist, optionally with its songs.
   *
   * @param  {String}     name  Name of the playlist
   * @param  {Array.<Object>} songs An array of song objects
   */
  store (name, songs = []) {
    if (songs.length) {
      // Extract the IDs from the song objects.
      songs = map(songs, 'id')
    }

    NProgress.start()

    return new Promise((resolve, reject) => {
      http.post('playlist', { name, songs }, ({ data: playlist }) => {
        playlist.songs = songs
        this.objectifySongs(playlist)
        this.add(playlist)
        alerts.success(`Created playlist &quot;${playlist.name}&quot;.`)
        resolve(playlist)
      }, error => reject(error))
    })
  },

  /**
   * Delete a playlist.
   *
   * @param  {Object}   playlist
   */
  delete (playlist) {
    NProgress.start()

    return new Promise((resolve, reject) => {
      http.delete(`playlist/${playlist.id}`, {}, ({ data }) => {
        this.remove(playlist)
        alerts.success(`Deleted playlist &quot;${playlist.name}&quot;.`)
        resolve(data)
      }, error => reject(error))
    })
  },

  /**
   * Add songs into a playlist.
   *
   * @param {Object}      playlist
   * @param {Array.<Object>}  songs
   */
  addSongs (playlist, songs) {
    return new Promise((resolve, reject) => {
      const count = playlist.songs.length
      playlist.songs = union(playlist.songs, songs)

      if (count === playlist.songs.length) {
        resolve(playlist)
        return
      }

      NProgress.start()

      http.put(`playlist/${playlist.id}/sync`, { songs: map(playlist.songs, 'id') }, () => {
        alerts.success(`Added ${pluralize(songs.length, 'song')} into &quot;${playlist.name}&quot;.`)
        resolve(playlist)
      }, error => reject(error))
    })
  },

  /**
   * Remove songs from a playlist.
   *
   * @param  {Object}     playlist
   * @param  {Array.<Object>} songs
   */
  removeSongs (playlist, songs) {
    NProgress.start()

    playlist.songs = difference(playlist.songs, songs)

    return new Promise((resolve, reject) => {
      http.put(`playlist/${playlist.id}/sync`, { songs: map(playlist.songs, 'id') }, () => {
        alerts.success(`Removed ${pluralize(songs.length, 'song')} from &quot;${playlist.name}&quot;.`)
        resolve(playlist)
      }, error => reject(error))
    })
  },

  /**
   * Update a playlist (just change its name).
   *
   * @param  {Object}   playlist
   */
  update (playlist) {
    NProgress.start()

    return new Promise((resolve, reject) => {
      http.put(
        `playlist/${playlist.id}`,
        { name: playlist.name },
        () => resolve(playlist),
        error => reject(error)
      )
    })
  }
}

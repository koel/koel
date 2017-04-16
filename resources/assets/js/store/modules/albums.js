/*eslint camelcase: ["error", {properties: "never"}]*/

import Vue from 'vue'
import { reduce, each, union, difference, take, filter, orderBy } from 'lodash'

import * as types from '../mutation-types'

const cache = []

const state = {
  albums: []
}

const getters = {
  allAlbums: state => state.albums,
  albumById: state => id => cache[id],
  isEmptyAlbum: state => album => !album.songs.length,

  getAlbumLength: state =>  album => {
    Vue.set(album, 'length', reduce(album.songs, (length, song) => length + song.length, 0))
    Vue.set(album, 'fmtLength', secondsToHis(album.length))
    return album.fmtLength
  },

  mostPlayedAlbums: state => (n = 6) => {
    // Only non-unknown albums with actually play count are applicable.
    const applicable = filter(state.albums, album => album.playCount && album.id !== 1)
    return take(orderBy(applicable, 'playCount', 'desc'), n)
  },

  recentlyAddedAlbums: state => (n = 6) => {
    const applicable = filter(state.albums, album => album.id !== 1)
    return take(orderBy(applicable, 'created_at', 'desc'), n)
  }
}

const actions = {
  initAlbums ({ dispatch, commit, state }, artists) {
    return new Promise(resolve => {
      // Traverse through the artists array and add their albums into our master album list.
      const albums = reduce(artists, (albums, artist) => {
        // While we're doing so, for each album, we get its length
        // and keep a back reference to the artist too.
        each(artist.albums, album => dispatch('setupAlbum', { album, artist }))
        return albums.concat(artist.albums)
      }, [])

      commit(types.ALBUM_INIT_STORE, albums)
      resolve(state.albums)
    })
  },

  setupAlbum ({ commit }, { album, artist }) {
    return new Promise(resolve => {
      album.playCount = 0
      album.artist = artist
      album.info = null
      cache[album.id] = album

      resolve(album)
    })
  },
}

const mutations = {
  [types.ALBUM_INIT_STORE] (state, albums) {
    state.albums = albums
  },

  [types.ALBUM_SETUP] (state, { album, artist }) {

  },

  [types.ALBUM_ADD] (state, { album }) {

  },

  [types.ALBUM_ADD_SONGS_INTO_ALBUM] (state, { album, songs }) {

  },

  [types.ALBUM_REMOVE_SONGS_FROM_ALBUM] (state, { album, songs }) {

  },

  [types.ALBUM_REMOVE] (state, { albums }) {

  },

  [types.SET_INTERACTION_DATA] (state, { song: { album }, playCount }) {
    album.playCount += playCount
  }
}

export default {
  state,
  getters,
  actions,
  mutations
}

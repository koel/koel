import { each, find, map, difference, union } from 'lodash'

import { http } from '../../services'
import * as types from '../mutation-types'

const state = {
  playlists: []
}

const getters = {
  allPlaylists: state => state.playlists,
  playlistById: state => id => find(state.playlists, { id }),
  songsInPlaylist: state => playlist => playlist.songs
}

const actions = {
  initPlaylists ({ dispatch, state, commit, getters }, { playlists }) {
    return new Promise(resolve => {
      each(playlists, playlist => {
        playlist.songs = getters.songsByIds(playlist.songs)
      })
      commit(types.PLAYLIST_INIT_STORE, playlists)
      resolve(state.playlists)
    })
  },

  addPlaylist ({ commit }, playlists) {

  },

  removePlaylist ({ commit }, playlists) {

  },

  storePlaylist ({ commit }, { name, songs }) {

  },

  deletePlaylist ({ commit }, playlist) {

  },

  addSongsIntoPlaylist ({ commit }, { playlist, songs }) {

  },

  removeSongsFromPlaylist ({ commit }, { playlist, songs }) {

  },

  updatePlaylist ({ commit }, playlist) {

  }
}

const mutations = {
  [types.PLAYLIST_INIT_STORE] (state, playlists) {
    state.playlists = playlists
  }
}

export default {
  state,
  getters,
  actions,
  mutations
}

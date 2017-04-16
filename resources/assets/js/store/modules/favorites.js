import { each, map, difference, union } from 'lodash'
import * as types from '../mutation-types'

const state = {
  songs: []
}

const getters = {
  allFavorites: state => state.songs
}

const actions = {
  toggleLike ({ commit }) {

  },

  addToFavorites ({ commit }, songs) {
    commit(types.FAVORITE_ADD, songs)
  },

  removeFromFavorites ({ commit }, songs) {

  },

  clearFavorites ({ commit }) {

  },

  like ({ commit }, songs) {

  },

  unlike ({ commit }, songs) {

  }
}

const mutations = {
  [types.FAVORITE_TOGGLE_ONE] (state, { song }) {

  },

  [types.FAVORITE_ADD] (state, { songs }) {
    state.songs = union(state.songs, [].concat(songs))
  },

  [types.FAVORITE_REMOVE] (state, { songs }) {

  },

  [types.FAVORITE_CLEAR] (state) {
    state.songs = []
  }
}

export default {
  state,
  getters,
  actions,
  mutations
}

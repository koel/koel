import { head, last, each, includes, union, difference, map, shuffle as _shuffle, first } from 'lodash'

import * as types from '../mutation-types'

const state = {
  songs: [],
  current: null
}

const getters = {
  allQueuedSongs: state => state.songs,
  firstQueuedSong: state => head(state.songs),
  lastQueuedSong: state => last(state.songs),
  containedInQueue: state => song => includes(state.songs, song),
  indexOfInQueue: state => song => state.songs.indexOf(song),
  nextInQueue: state => {

  },
  previousInQueue: state => {

  }
}

const actions = {
  queue ({ commit }, { songs, replace = true, toTop = true}) {

  },

  queueAfterCurrent ({ commit }, songs) {

  },

  unqueue ({ commit }, songs) {

  },

  moveInQueue ({ commit }, { songs, target }) {

  },

  shuffleQueue ({ commit }) {

  },

  clearQueue ({ commit }) {

  }
}

const mutations = {
  [types.QUEUE_QUEUE] (state) {

  }
}

export default {
  state,
  getters,
  actions,
  mutations
}

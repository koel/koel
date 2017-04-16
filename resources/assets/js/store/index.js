import Vue from 'vue'
import Vuex from 'vuex'
import state from './state'
import actions from './actions'
import getters from './getters'
import mutations from './mutations'
import albums from './modules/albums'
import artists from './modules/artists'
import equalizer from './modules/equalizer'
import favorites from './modules/favorites'
import playlists from './modules/playlists'
import preferences from './modules/preferences'
import queue from './modules/queue'
import settings from './modules/settings'
import songs from './modules/songs'
import users from './modules/users'

Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production'

export default new Vuex.Store({
  state,
  actions,
  getters,
  mutations,
  modules: {
    albums,
    artists,
    equalizer,
    favorites,
    playlists,
    preferences,
    queue,
    settings,
    songs,
    users
  },
  strict: debug
})

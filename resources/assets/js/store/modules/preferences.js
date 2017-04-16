import { extend, has, each } from 'lodash'

import { ls } from '../../services'
import * as types from '../mutation-types'

let storeKey = ''

const state = {
  volume: 7,
  notify: true,
  repeatMode: 'NO_REPEAT',
  showExtraPanel: true,
  confirmClosing: false,
  equalizer: {
    preamp: 0,
    gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
  },
  artistsViewMode: null,
  albumsViewMode: null,
  selectedPreset: -1,
  transcodeOnMobile: false
}

const getters = {
  preferenceByKey: state => key => state[key]
}

const actions = {
  initPreferences ({ commit, getters }, { currentUser: { preferences }}) {
    return new Promise(resolve => {
      commit(types.PREFERENCE_INIT_STORE, {
        preferences,
        user: getters.currentUser
      })
      resolve()
    })
  },

  setPreferences ({ commit }, { key, val }) {
    commit (types.PREFERENCE_SAVE, { key, val })
  }
}

const mutations = {
  [types.PREFERENCE_INIT_STORE] (state, { user, preferences }) {
    storeKey = `preferences_${user.id}`
    extend(state, ls.get(storeKey, state, {}))
    // setupProxy()
  },

  [types.PREFERENCE_SAVE] (state) {
    ls.set(storeKey, state)
  }
}

export default {
  state,
  getters,
  actions,
  mutations
}

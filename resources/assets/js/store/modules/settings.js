import { http } from '../../services'
import * as types from '../mutation-types'

const state = {
  settings: []
}

const getters = {
  allSettings: state => state.settings
}

const actions = {
  initSettings ({ commit }, { settings }) {
    return new Promise(resolve => {
      commit(types.SETTING_INIT_STORE, settings)
      resolve()
    })
  },

  updateSettings ({ commit }) {

  }
}

const mutations = {
  [types.SETTING_INIT_STORE] (state, settings) {
    state.settings = settings
  }
}

export default {
  state,
  getters,
  actions,
  mutations
}

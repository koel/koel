import { each, find, without } from 'lodash'
import md5 from 'blueimp-md5'
import Vue from 'vue'
import NProgress from 'nprogress'

import { http, ls } from '../../services'
import { alerts } from '../../utils'
import * as types from '../mutation-types'

const state = {
  users: [],
  current: {},
  jwtToken: ls.get('jwt-token')
}

const getters = {
  allUsers: state => state.users,
  userById: state => id => find(state.users, { id }),
  currentUser: state => state.current,
  jwtToken: state => state.jwtToken
}

const actions = {
  initUsers ({ commit }, { users, currentUser }) {
    return new Promise(resolve => {
      commit(types.USER_INIT_STORE, users)
      commit(types.USER_SET_CURRENT, currentUser)
      each(users, user => commit (types.USER_SET_AVATAR, user))
      resolve()
    })
  },

  setAvatar ({ commit, state }, user = null) {
    if (!user) {
      user = state.current
    }
  },

  login ({ commit }, credentials) {
    NProgress.start()

    return new Promise((resolve, reject) => {
      http.post('me', credentials, ({ data }) => {
        commit(types.USER_SET_JWT_TOKEN, data.token)
        resolve(data)
      }, error => reject(error))
    })
  },

  logout ({ commit }) {
    return new Promise((resolve, reject) => {
      http.delete('me', {}, ({ data }) => {
        commit(types.USER_DELETE_JWT_TOKEN)
        resolve(data)
      }, error => reject(error))
    })
  },

  updateProfile ({ commit }, { password }) {

  },

  storeUser ({ commit }, profile) {

  },

  updateUser ({ commit }, { user, profile }) {

  },

  destroyUser ({ commit }, user) {

  }
}

const mutations = {
  [types.USER_INIT_STORE] (state, users) {
    state.users = users
  },

  [types.USER_SET_CURRENT] (state, currentUser) {
    state.current = currentUser
  },

  [types.USER_SET_AVATAR] (state, user) {
    if (!user) {
      user = state.current
    }

    user.avatar = `https://www.gravatar.com/avatar/${md5(user.email)}?s=256`
  },

  [types.USER_SET_JWT_TOKEN] (state, token) {
    ls.set('jwt-token', token)
    state.jwtToken = token
  },

  [types.USER_DELETE_JWT_TOKEN] (state) {
    ls.remove('jwt-token')
    state.jwtToken = null
  }
}

export default {
  state,
  getters,
  actions,
  mutations
}

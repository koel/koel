import { assign } from 'lodash'
import isMobile from 'ismobilejs'

import { http } from '../services'
import * as types from './mutation-types'

const actions = {
  initGlobal ({ commit }) {
    return new Promise((resolve, reject) => {
      http.get('data', ({ data }) => {
        // Don't allow downloading on mobile devices
        data.allowDownload = data.allowDownload && !isMobile.any
        // Disable YouTube integration on mobile
        data.useYouTube = data.useYouTube && !isMobile.phone
        // If this is a new user, initialize his preferences as an empty object
        if (!data.currentUser.preferences) {
          data.currentUser.preferences = {}
        }
        // Keep a copy of the media path. We'll need this to properly warn the user later.
        data.originalMediaPath = data.settings.media_path
        commit(types.GLOBAL_INIT_DATA, data)

        resolve(data)
      }, error => reject(error))
    })
  }
}

export default actions

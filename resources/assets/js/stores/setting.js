import { http } from '../services'
import stub from '../stubs/settings'

export const settingStore = {
  stub,

  state: {
    settings: []
  },

  init (settings) {
    this.state.settings = settings
  },

  get all () {
    return this.state.settings
  },

  update () {
    return new Promise((resolve, reject) => {
      http.post('settings', this.all, data => resolve(data), r => reject(r))
    })
  }
}

import { userStore } from '.'
import { ls } from '@/services'

export const preferenceStore = {
  storeKey: '',

  state: {
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
  },

  /**
   * Init the store.
   *
   * @param  {Object} user The user whose preferences we are managing.
   */
  init (user = null) {
    user = user ||userStore.current
    this.storeKey = `preferences_${user.id}`
    this.state = Object.assign(this.state, ls.get(this.storeKey, this.state))
    this.setupProxy()
  },

  /**
   * Proxy the state properties, so that each can be directly accessed using the key.
   */
  setupProxy () {
    Object.keys(this.state).forEach(key => {
      Object.defineProperty(this, key, {
        get: () => this.get(key),
        set: value => this.set(key, value),
        configurable: true
      })
    })
  },

  set (key, val) {
    this.state[key] = val
    this.save()
  },

  get (key) {
    return this.state.hasOwnProperty(key) ? this.state[key] : null
  },

  save () {
    ls.set(this.storeKey, this.state)
  }
}

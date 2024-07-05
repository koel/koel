import { reactive, ref } from 'vue'
import { http } from '@/services'

export const defaultPreferences: UserPreferences = {
  volume: 7,
  show_now_playing_notification: true,
  repeat_mode: 'NO_REPEAT',
  confirm_before_closing: false,
  equalizer: {
    name: 'Default',
    preamp: 0,
    gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
  },
  artists_view_mode: null,
  albums_view_mode: null,
  transcode_on_mobile: false,
  support_bar_no_bugging: false,
  show_album_art_overlay: true,
  lyrics_zoom_level: 1,
  theme: null,
  visualizer: 'default',
  active_extra_panel_tab: null,
  make_uploads_public: false,
  continuous_playback: false
}

const preferenceStore = {
  _temporary: false,
  initialized: ref(false),

  state: reactive<UserPreferences>(defaultPreferences),

  init (preferences: UserPreferences = defaultPreferences) {
    Object.assign(this.state, preferences)
    this.setupProxy()

    this.initialized.value = true
  },

  /**
   * Proxy the state properties, so that each can be directly accessed using the key.
   */
  setupProxy () {
    Object.keys(this.state).forEach(key => {
      Object.defineProperty(this, key, {
        get: (): any => this.get(key),
        set: (value: any): void => this.set(key, value),
        configurable: true
      })
    })
  },

  set (key: keyof UserPreferences, value: any) {
    if (this.state[key] === value) return

    this.state[key] = value

    if (!this._temporary) {
      http.silently.patch('me/preferences', { key, value })
    } else {
      this._temporary = false
    }
  },

  get (key: keyof UserPreferences) {
    return this.state?.[key]
  },

  // Calling preferenceStore.temporary.volume = 7 won't trigger saving.
  // This is useful in tests as it doesn't create stray HTTP requests.
  get temporary () {
    this._temporary = true
    return this as unknown as ExportedType
  }
}

type ExportedType = Omit<typeof preferenceStore, 'setupProxy' | '_temporary'> & UserPreferences

const exported = preferenceStore as unknown as ExportedType

export { exported as preferenceStore }

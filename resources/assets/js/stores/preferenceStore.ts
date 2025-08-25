import { reactive, ref } from 'vue'
import { http } from '@/services/http'

export const defaultPreferences: UserPreferences = {
  volume: 7,
  show_now_playing_notification: true,
  repeat_mode: 'NO_REPEAT',
  confirm_before_closing: false,
  equalizer: {
    name: 'Default',
    preamp: 0,
    gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
  },
  albums_view_mode: 'thumbnails',
  artists_view_mode: 'thumbnails',
  radio_stations_view_mode: 'thumbnails',
  albums_sort_field: 'name',
  artists_sort_field: 'name',
  genres_sort_field: 'name',
  podcasts_sort_field: 'title',
  radio_stations_sort_field: 'name',
  albums_sort_order: 'asc',
  artists_sort_order: 'asc',
  genres_sort_order: 'asc',
  podcasts_sort_order: 'asc',
  radio_stations_sort_order: 'asc',
  albums_favorites_only: false,
  artists_favorites_only: false,
  podcasts_favorites_only: false,
  radio_stations_favorites_only: false,
  transcode_on_mobile: false,
  transcode_quality: 128,
  support_bar_no_bugging: false,
  show_album_art_overlay: true,
  lyrics_zoom_level: 1,
  theme: 'classic',
  visualizer: 'default',
  active_extra_panel_tab: null,
  make_uploads_public: false,
  include_public_media: true,
  continuous_playback: false,
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
        configurable: true,
      })
    })
  },

  set (key: keyof UserPreferences, value: any) {
    if (this.state[key] === value) {
      return
    }

    this.state[key] = value

    if (!this._temporary) {
      this.update(key, value)
    } else {
      this._temporary = false
    }
  },

  get (key: keyof UserPreferences) {
    return this.state?.[key]
  },

  async update (key: keyof UserPreferences, value: any) {
    await http.silently.patch('me/preferences', { key, value })

    if (key === 'include_public_media') {
      window.location.reload()
    }
  },

  // Calling preferenceStore.temporary.volume = 7 won't trigger saving.
  // This is useful in tests as it doesn't create stray HTTP requests.
  get temporary () {
    this._temporary = true
    return this as unknown as ExportedType
  },
}

type ExportedType = Omit<typeof preferenceStore, 'setupProxy' | '_temporary'> & UserPreferences

const exported = preferenceStore as unknown as ExportedType

export { exported as preferenceStore }

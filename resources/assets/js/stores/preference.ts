import { userStore } from '.'
import { ls } from '@/services'

interface Preferences extends Record<string, any> {
  volume: number
  notify: boolean
  repeatMode: RepeatMode
  showExtraPanel: boolean
  confirmClosing: boolean
  equalizer: EqualizerPreset,
  artistsViewMode: ArtistAlbumViewMode | null,
  albumsViewMode: ArtistAlbumViewMode | null,
  selectedPreset: number
  transcodeOnMobile: boolean
  supportBarNoBugging: boolean
  showAlbumArtOverlay: boolean
  theme: Theme['id'] | null
}

const preferenceStore = {
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
    transcodeOnMobile: false,
    supportBarNoBugging: false,
    showAlbumArtOverlay: true,
    theme: null
  } as Preferences,

  init (user?: User): void {
    const initUser = user || userStore.current
    this.storeKey = `preferences_${initUser.id}`
    this.state = Object.assign(this.state, ls.get(this.storeKey, this.state))
    this.setupProxy()
  },

  /**
   * Proxy the state properties, so that each can be directly accessed using the key.
   */
  setupProxy (): void {
    Object.keys(this.state).forEach(key => {
      Object.defineProperty(this, key, {
        get: (): any => this.get(key),
        set: (value: any): void => this.set(key, value),
        configurable: true
      })
    })
  },

  set (key: keyof Preferences, val: any): void {
    this.state[key] = val
    this.save()
  },

  get (key: string): any {
    return key in this.state ? this.state[key] : null
  },

  save (): void {
    ls.set(this.storeKey, this.state)
  }
}

const exportedPreferenceStore = preferenceStore as unknown as Omit<typeof preferenceStore, 'setupProxy'> & Preferences

export { exportedPreferenceStore as preferenceStore }

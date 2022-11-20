import { reactive, ref } from 'vue'
import { localStorageService } from '@/services'

interface Preferences extends Record<string, any> {
  volume: number
  notify: boolean
  repeatMode: RepeatMode
  confirmClosing: boolean
  equalizer: EqualizerPreset,
  artistsViewMode: ArtistAlbumViewMode | null,
  albumsViewMode: ArtistAlbumViewMode | null,
  transcodeOnMobile: boolean
  supportBarNoBugging: boolean
  showAlbumArtOverlay: boolean
  lyricsZoomLevel: number | null
  theme?: Theme['id'] | null
  visualizer?: Visualizer['id'] | null
  activeExtraPanelTab: ExtraPanelTab | null
}

const preferenceStore = {
  storeKey: '',
  initialized: ref(false),

  state: reactive<Preferences>({
    volume: 7,
    notify: true,
    repeatMode: 'NO_REPEAT',
    confirmClosing: false,
    equalizer: {
      id: 0,
      name: 'Default',
      preamp: 0,
      gains: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
    },
    artistsViewMode: null,
    albumsViewMode: null,
    transcodeOnMobile: false,
    supportBarNoBugging: false,
    showAlbumArtOverlay: true,
    lyricsZoomLevel: 1,
    theme: null,
    visualizer: 'default',
    activeExtraPanelTab: null
  }),

  init (user: User): void {
    this.storeKey = `preferences_${user.id}`
    Object.assign(this.state, localStorageService.get(this.storeKey, this.state))
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

  set (key: keyof Preferences, val: any) {
    this.state[key] = val
    this.save()
  },

  get (key: string) {
    return this.state?.[key]
  },

  save () {
    localStorageService.set(this.storeKey, this.state)
  }
}

const exported = preferenceStore as unknown as Omit<typeof preferenceStore, 'setupProxy'> & Preferences

export { exported as preferenceStore }

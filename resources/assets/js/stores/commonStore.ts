import isMobile from 'ismobilejs'
import { reactive } from 'vue'
import { httpService } from '@/services'
import { playlistStore, preferenceStore, settingStore, themeStore, userStore } from '@/stores'

interface CommonStoreState {
  allow_download: boolean
  cdn_url: string
  current_user: User
  current_version: string
  latest_version: string
  playlists: Playlist[]
  settings: Settings
  use_i_tunes: boolean
  use_last_fm: boolean
  use_spotify: boolean
  users: User[]
  use_you_tube: boolean,
  song_count: number,
  song_length: number
}

export const commonStore = {
  state: reactive<CommonStoreState>({
    allow_download: false,
    cdn_url: '',
    current_user: undefined as unknown as User,
    current_version: '',
    latest_version: '',
    playlists: [],
    settings: {} as Settings,
    use_i_tunes: false,
    use_last_fm: false,
    use_spotify: false,
    users: [],
    use_you_tube: false,
    song_count: 0,
    song_length: 0
  }),

  async init () {
    Object.assign(this.state, await httpService.get<CommonStoreState>('data'))

    // Always disable YouTube integration on mobile.
    this.state.use_you_tube = this.state.use_you_tube && !isMobile.phone

    // If this is a new user, initialize his preferences to be an empty object.
    this.state.current_user.preferences = this.state.current_user.preferences || {}

    userStore.init(this.state.current_user)
    preferenceStore.init(this.state.current_user)
    playlistStore.init(this.state.playlists)
    settingStore.init(this.state.settings)
    themeStore.init()

    return this.state
  }
}

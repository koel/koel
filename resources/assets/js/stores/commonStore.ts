import isMobile from 'ismobilejs'
import { reactive } from 'vue'
import { http } from '@/services'
import { playlistFolderStore, playlistStore, preferenceStore, queueStore, settingStore, themeStore, userStore } from '.'

interface CommonStoreState {
  allows_download: boolean
  cdn_url: string
  current_user: User
  current_version: string
  latest_version: string
  koel_plus: {
    active: boolean
    short_key: string | null
    customer_name: string | null
    customer_email: string | null
    product_id: string
  }
  media_path_set: boolean
  playlists: Playlist[]
  playlist_folders: PlaylistFolder[]
  queue_state: QueueState,
  settings: Settings
  song_count: number,
  song_length: number,
  uses_i_tunes: boolean
  uses_last_fm: boolean
  uses_spotify: boolean
  uses_you_tube: boolean
  users: User[]
}

export const commonStore = {
  state: reactive<CommonStoreState>({
    allows_download: false,
    cdn_url: '',
    current_user: undefined as unknown as User,
    current_version: '',
    koel_plus: {
      active: false,
      short_key: null,
      customer_name: null,
      customer_email: null,
      product_id: ''
    },
    latest_version: '',
    media_path_set: false,
    playlists: [],
    playlist_folders: [],
    settings: {} as Settings,
    uses_i_tunes: false,
    uses_last_fm: false,
    uses_spotify: false,
    users: [],
    uses_you_tube: false,
    song_count: 0,
    song_length: 0,
    queue_state: {
      type: 'queue-states',
      songs: [],
      current_song: null,
      playback_position: 0
    }
  }),

  async init () {
    Object.assign(this.state, await http.get<CommonStoreState>('data'))

    // Always disable YouTube integration on mobile.
    this.state.uses_you_tube = this.state.uses_you_tube && !isMobile.phone

    // If this is a new user, initialize his preferences to be an empty object.
    this.state.current_user.preferences = this.state.current_user.preferences || {}

    userStore.init(this.state.current_user)
    preferenceStore.init(this.state.current_user.preferences)
    playlistStore.init(this.state.playlists)
    playlistFolderStore.init(this.state.playlist_folders)
    settingStore.init(this.state.settings)
    queueStore.init(this.state.queue_state)
    themeStore.init()

    return this.state
  }
}

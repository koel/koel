import isMobile from 'ismobilejs'
import { reactive } from 'vue'
import { http } from '@/services'
import { playlistFolderStore, playlistStore, preferenceStore, queueStore, settingStore, themeStore, userStore } from '.'

interface CommonStoreState {
  allow_download: boolean
  cdn_url: string
  current_user: User
  current_version: string
  latest_version: string
  playlists: Playlist[]
  playlist_folders: PlaylistFolder[]
  settings: Settings
  use_i_tunes: boolean
  use_last_fm: boolean
  use_spotify: boolean
  users: User[]
  use_you_tube: boolean,
  song_count: number,
  song_length: number,
  queue_state: QueueState
}

export const commonStore = {
  state: reactive<CommonStoreState>({
    allow_download: false,
    cdn_url: '',
    current_user: undefined as unknown as User,
    current_version: '',
    latest_version: '',
    playlists: [],
    playlist_folders: [],
    settings: {} as Settings,
    use_i_tunes: false,
    use_last_fm: false,
    use_spotify: false,
    users: [],
    use_you_tube: false,
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
    this.state.use_you_tube = this.state.use_you_tube && !isMobile.phone

    // If this is a new user, initialize his preferences to be an empty object.
    this.state.current_user.preferences = this.state.current_user.preferences || {}

    userStore.init(this.state.current_user)
    preferenceStore.init(this.state.current_user)
    playlistStore.init(this.state.playlists)
    playlistFolderStore.init(this.state.playlist_folders)
    settingStore.init(this.state.settings)
    queueStore.init(this.state.queue_state)
    themeStore.init()

    return this.state
  }
}

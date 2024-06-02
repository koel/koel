import isMobile from 'ismobilejs'
import { reactive } from 'vue'
import { http } from '@/services'
import { playlistFolderStore, playlistStore, preferenceStore, queueStore, settingStore, themeStore, userStore } from '.'

const initialState = {
  allows_download: false,
  cdn_url: '',
  current_user: undefined as unknown as User,
  current_version: '',
  koel_plus: {
    active: false,
    short_key: null as string | null,
    customer_name: null as string | null,
    customer_email: null as string | null,
    product_id: '' as string | null
  },
  latest_version: '',
  media_path_set: false,
  playlists: [] as Playlist[],
  playlist_folders: [] as PlaylistFolder[],
  settings: {} as Settings,
  uses_i_tunes: false,
  uses_last_fm: false,
  uses_spotify: false,
  users: [] as User[],
  uses_you_tube: false,
  storage_driver: 'local',
  song_count: 0,
  song_length: 0,
  queue_state: {
    type: 'queue-states',
    playables: [],
    current_song: null,
    playback_position: 0
  } as QueueState
}

type CommonStoreState = typeof initialState

export const commonStore = {
  state: reactive<CommonStoreState>(initialState),

  async init () {
    Object.assign(this.state, await http.get<CommonStoreState>('data'))

    // Always disable YouTube integration on mobile.
    this.state.uses_you_tube = this.state.uses_you_tube && !isMobile.phone

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

import isMobile from 'ismobilejs'
import { reactive } from 'vue'
import { http } from '@/services/http'
import { playlistFolderStore } from '@/stores/playlistFolderStore'
import { playlistStore } from '@/stores/playlistStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { queueStore } from '@/stores/queueStore'
import { settingStore } from '@/stores/settingStore'
import { themeStore } from '@/stores/themeStore'
import { userStore } from '@/stores/userStore'

const initialState = {
  allows_download: false,
  cdn_url: '',
  current_user: null! as User,
  current_version: '',
  koel_plus: {
    active: false,
    short_key: null as string | null,
    customer_name: null as string | null,
    customer_email: null as string | null,
    product_id: '' as string | null,
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
    songs: [],
    current_song: null,
    playback_position: 0,
  } as QueueState,
  supports_batch_downloading: false,
  supports_transcoding: false,
}

type CommonStoreState = typeof initialState

export const commonStore = {
  state: reactive<CommonStoreState>(initialState),

  async init () {
    Object.assign(this.state, await http.get<CommonStoreState>('data'))

    // Always disable YouTube integration on mobile.
    this.state.uses_you_tube = this.state.uses_you_tube && !isMobile.any

    // Only enable transcoding on mobile
    this.state.supports_transcoding = this.state.supports_transcoding && isMobile.any

    userStore.init(this.state.current_user)
    preferenceStore.init(this.state.current_user.preferences)
    playlistStore.init(this.state.playlists)
    playlistFolderStore.init(this.state.playlist_folders)
    settingStore.init(this.state.settings)
    queueStore.init(this.state.queue_state)
    themeStore.init()

    return this.state
  },
}

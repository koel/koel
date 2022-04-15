import isMobile from 'ismobilejs'

import { http } from '@/services'
import {
  userStore,
  preferenceStore,
  artistStore,
  albumStore,
  songStore,
  playlistStore,
  recentlyPlayedStore,
  queueStore,
  settingStore, themeStore
} from '.'

interface SharedState {
  albums: Album[]
  allowDownload: boolean
  artists: Artist[]
  cdnUrl: string
  currentUser: User | undefined
  currentVersion: string
  favorites: Song[]
  interactions: Interaction[]
  latestVersion: string
  originalMediaPath: string | undefined
  playlists: Playlist[]
  queued: Song[]
  recentlyPlayed: string[]
  settings: Settings
  songs: Song[]
  useiTunes: boolean
  useLastfm: boolean
  users: User[]
  useYouTube: boolean
}

export const sharedStore = {
  state: {
    albums: [],
    allowDownload: false,
    artists: [],
    cdnUrl: '',
    currentUser: null as User | null,
    currentVersion: '',
    favorites: [],
    interactions: [],
    latestVersion: '',
    originalMediaPath: '',
    playlists: [],
    queued: [],
    recentlyPlayed: [],
    settings: {} as Settings,
    songs: [],
    useiTunes: false,
    useLastfm: false,
    users: [],
    useYouTube: false
  } as SharedState,

  async init (): Promise<SharedState> {
    this.state = Object.assign(this.state, await http.get<SharedState>('data'))

    // Always disable YouTube integration on mobile.
    this.state.useYouTube = this.state.useYouTube && !isMobile.phone

    // If this is a new user, initialize his preferences to be an empty object.
    this.state.currentUser!.preferences = this.state.currentUser!.preferences || {}

    userStore.init(this.state.users, this.state.currentUser!)
    preferenceStore.init(this.state.currentUser)
    artistStore.init(this.state.artists)
    albumStore.init(this.state.albums)
    songStore.init(this.state.songs)
    songStore.initInteractions(this.state.interactions)
    recentlyPlayedStore.initExcerpt(this.state.recentlyPlayed)
    playlistStore.init(this.state.playlists)
    queueStore.init()
    settingStore.init(this.state.settings)
    themeStore.init()

    // Keep a copy of the media path. We'll need this to properly warn the user later.
    this.state.originalMediaPath = this.state.settings.media_path!

    return this.state
  }
}

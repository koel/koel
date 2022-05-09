declare let KOEL_ENV: '' | 'demo'

declare module '*.vue'

declare module '*.jpg' {
  const value: string
  export default value
}

declare module '*.png' {
  const value: string
  export default value
}

declare module '*.svg' {
  const value: string
  export default value
}

declare type Closure = (...args: Array<unknown | any>) => unknown | any

declare module 'alertify.js' {
  function alert (msg: string): void

  function confirm (msg: string, okFunc: Closure, cancelFunc?: Closure): void

  function success (msg: string, cb?: Closure): void

  function error (msg: string, cb?: Closure): void

  function log (msg: string, cb?: Closure): void

  function logPosition (position: string): void

  function closeLogOnClick (close: boolean): void
}

declare module 'sketch-js' {
  function create (config: Record<string, any>): any
}

declare module 'youtube-player' {
  import { YouTubePlayer } from 'youtube-player/dist/types'

  function createYouTubePlayer (name: string, options: Record<string, any>): YouTubePlayer

  export default createYouTubePlayer
}

interface Plyr {
  media: HTMLMediaElement

  restart (): void

  play (): void

  pause (): void

  seek (position: number): void

  setVolume (volume: number): void
}

declare module 'plyr' {
  function setup (el: HTMLMediaElement | HTMLMediaElement[], options: Record<string, any>): Plyr[]
}

declare module 'ismobilejs' {
  let apple: { device: boolean }
  let any: boolean
  let phone: boolean
}

declare module 'nouislider' {
  function create (el: HTMLElement, config: {
    connect: boolean[]
    start: number
    range: {
      min: number
      max: number
    }
    orientation: 'horizontal' | 'vertical'
    direction: 'ltr' | 'rtl'
  }): void
}

interface Constructable<T> {
  new (...args: any): T
}

interface Window {
  BASE_URL: string
  __UNIT_TESTING__: boolean
  readonly PUSHER_APP_KEY: string
  readonly PUSHER_APP_CLUSTER: string
  readonly webkitAudioContext: Constructable<AudioContext>
  readonly mozAudioContext: Constructable<AudioContext>
  readonly oAudioContext: Constructable<AudioContext>
  readonly msAudioContext: Constructable<AudioContext>
  readonly MediaMetadata: Constructable<Record<string, any>>
}

interface FileSystemDirectoryReader {
  readEntries (successCallback: Closure, errorCallback?: Closure): FileSystemEntry[]
}

interface FileSystemEntry {
  readonly isFile: boolean
  readonly isDirectory: boolean
  readonly name: string
  readonly fullPath: string
  readonly filesystem: FileSystem

  createReader (): FileSystemDirectoryReader

  file (successCallback: Closure): void
}

interface AlbumTrack {
  readonly title: string
  readonly length: number
  fmtLength: string
}

interface AlbumInfo {
  image: string | null
  readonly tracks: AlbumTrack[]
  wiki?: {
    summary: string
    full: string
  }
  url?: string
}

interface ArtistInfo {
  image: string | null
  bio?: {
    summary: string
    full: string
  }
  url?: string
}

interface Artist {
  readonly id: number
  name: string
  image: string | null
  albums: Album[]
  songs: Song[]
  info: ArtistInfo | null
  playCount: number
  length: number
  fmtLength: string
}

interface Album {
  is_compilation: any
  readonly id: number
  artist_id: number
  artist: Artist
  name: string
  cover: string
  thumbnail?: string | null
  songs: Song[]
  info: AlbumInfo | null
  playCount: number
  length: number
  fmtLength: string
}

interface Song {
  readonly id: string
  album_id: number
  album: Album
  artist_id: number
  artist: Artist
  title: string
  readonly length: number
  track: number
  disc: number
  lyrics: string
  youtube?: {
    items: YouTubeVideo[]
    nextPageToken: string
  },
  playCountRegistered?: boolean
  preloaded?: boolean
  playbackState?: PlaybackState
  infoRetrieved?: boolean
  playCount: number
  liked: boolean
  playStartTime?: number
  fmtLength?: string
}

interface SmartPlaylistRuleGroup {
  id: number
  rules: SmartPlaylistRule[]
}

interface SmartPlaylistModel {
  name: string
  type: string
  label: string
  unit?: string
}

interface SmartPlaylistOperator {
  operator: string
  label: string
  type?: string
  unit?: string
  inputs?: number
}

interface SmartPlaylistRule {
  id: number
  model: SmartPlaylistModel
  operator: string
  value: any[]
}

type SmartPlaylistInputTypes = Record<string, SmartPlaylistOperator[]>

type PlaylistType = 'playlist' | 'favorites' | 'recently-played'

interface Playlist {
  readonly id: number
  name: string
  songs: Song[]
  populated?: boolean
  is_smart: boolean
  rules: SmartPlaylistRuleGroup[]
}

interface YouTubeVideo {
  readonly id: {
    videoId: string
  }

  readonly snippet: {
    title: string
    description: string
    thumbnails: {
      default: {
        url: string
      }
    }
  }
}

interface UserPreferences {
  lastfm_session_key?: string
}

interface User {
  id: number
  name: string
  email: string
  password: string
  is_admin: boolean
  preferences: UserPreferences
  avatar: string
}

interface Settings {
  media_path?: string
}

interface Interaction {
  readonly song_id: string
  liked: boolean
  play_count: number
}

declare module 'koel/types/ui' {
  interface SliderElement extends HTMLElement {
    noUiSlider?: {
      destroy (): void
      on (eventName: 'change' | 'slide', handler: (value: number[], handle: number) => unknown): void
      set (options: number | any[]): void
    }
  }
}

interface SongRow {
  song: Song
  selected: boolean
}

interface EqualizerPreset {
  id?: number
  name?: string
  preamp: number
  gains: number[]
}

declare type DragType = 'Song' | 'Album' | 'Artist'
declare type PlaybackState = 'Stopped' | 'Playing' | 'Paused'
declare type MainViewName =
  | 'Home'
  | 'Default'
  | 'Queue'
  | 'Songs'
  | 'Albums'
  | 'Artists'
  | 'Favorites'
  | 'RecentlyPlayed'
  | 'Settings'
  | 'Users'
  | 'YouTube'
  | 'Visualizer'
  | 'Profile'
  | 'Album'
  | 'Artist'
  | 'Playlist'
  | 'Upload'
  | 'Search.Excerpt'
  | 'Search.Songs'

declare type ArtistAlbumCardLayout = 'full' | 'compact'

interface SongUploadResult {
  album: {
    id: number
    name: string
    cover: string
    is_compilation: boolean
    artist_id: number
  }
  artist: {
    id: number
    name: string
    image: string | null
  }
  id: string
  title: string
  length: number
  disc: number
  track: number
}

interface AddToMenuConfig {
  queue: boolean
  favorites: boolean
  playlists: boolean
  newPlaylist: boolean
}

interface SongListControlsConfig {
  play: boolean
  addTo: AddToMenuConfig
  clearQueue: boolean
  deletePlaylist: boolean
}

interface Theme {
  id: string
  name?: string
  thumbnailColor: string
  thumbnailUrl?: string
  selected?: boolean
}

type ArtistAlbumViewMode = 'list' | 'thumbnails'

type RepeatMode = 'NO_REPEAT' | 'REPEAT_ALL' | 'REPEAT_ONE'

type SongListType = 'all-songs'
  | 'queue'
  | 'playlist'
  | 'favorites'
  | 'recently-played'
  | 'artist'
  | 'album'
  | 'search-results'

type SongListColumn = 'track' | 'title' | 'album' | 'artist' | 'length'

interface SongListConfig {
  sortable: boolean
  columns: SongListColumn[]
}

type SongListSortField = 'song.track'
  | 'song.disc'
  | 'song.title'
  | 'song.album.artist.name'
  | 'song.album.name'
  | 'song.length'

type MethodOf<T> = { [K in keyof T]: T[K] extends Closure ? K : never; }[keyof T]

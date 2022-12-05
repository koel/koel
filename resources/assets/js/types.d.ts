declare module '*.vue'
declare module '*.jpg'
declare module '*.png'
declare module '*.svg'

declare type Closure<T = unknown | any> = (...args: Array<unknown | any>) => T

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
    step?: number
  }): void
}

interface Constructable<T> {
  new (...args: any): T
}

interface Window {
  BASE_URL: string
  readonly PUSHER_APP_KEY: string
  readonly PUSHER_APP_CLUSTER: string
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

type MediaInfoDisplayMode = 'aside' | 'full'
type ScreenHeaderLayout = 'expanded' | 'collapsed'

interface AlbumTrack {
  readonly title: string
  readonly length: number
}

interface AlbumInfo {
  cover: string | null
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
  type: 'artists',
  readonly id: number
  name: string
  image: string | null
  created_at: string
}

interface Album {
  type: 'albums'
  readonly id: number
  artist_id: Artist['id']
  artist_name: Artist['name']
  name: string
  cover: string
  thumbnail?: string | null
  created_at: string
}

interface Song {
  type: 'songs'
  readonly id: string
  album_id: Album['id']
  album_name: Album['name']
  album_cover: Album['cover']
  artist_id: Artist['id']
  artist_name: Artist['name']
  album_artist_id: Artist['id']
  album_artist_name: Artist['name']
  title: string
  readonly length: number
  track: number | null
  disc: number | null
  genre: string
  year: number | null
  lyrics: string
  play_count_registered?: boolean
  preloaded?: boolean
  playback_state?: PlaybackState
  play_count: number
  liked: boolean
  play_start_time?: number
  fmt_length?: string
  created_at: string
  deleted?: boolean
}

interface SmartPlaylistRuleGroup {
  id: number
  rules: SmartPlaylistRule[]
}

interface SmartPlaylistModel {
  name: 'title' | 'length' | 'created_at' | 'updated_at' | 'album.name' | 'artist.name' | 'interactions.play_count' | 'interactions.last_played_at' | 'genre' | 'year'
  type: 'text' | 'number' | 'date'
  label: string
  unit?: 'seconds' | 'days'
}

interface SmartPlaylistOperator {
  operator: 'is' | 'isNot' | 'contains' | 'notContain' | 'isBetween' | 'isGreaterThan' | 'isLessThan' | 'beginsWith' | 'endsWith' | 'inLast' | 'notInLast'
  label: string
  type?: SmartPlaylistModel['type'] // to override
  unit?: SmartPlaylistModel['unit'] // to override
  inputs?: number
}

interface SmartPlaylistRule {
  id: number
  model: SmartPlaylistModel
  operator: SmartPlaylistOperator['operator']
  value: any[]
}

interface SerializedSmartPlaylistRule {
  id: number
  model: SmartPlaylistModel['name']
  operator: SmartPlaylistOperator['operator']
  value: any[]
}

type SmartPlaylistInputTypes = Record<SmartPlaylistModel['type'], SmartPlaylistOperator[]>

type FavoriteList = {
  name: 'Favorites'
  songs: Song[]
}

type RecentlyPlayedList = {
  name: 'Recently Played'
  songs: Song[]
}

interface PlaylistFolder {
  type: 'playlist-folders'
  readonly id: string
  name: string
  // we don't need to keep track of the playlists here, as they can be computed using their folder_id value
}

interface Playlist {
  type: 'playlists'
  readonly id: number
  name: string
  folder_id: PlaylistFolder['id'] | null
  is_smart: boolean
  rules: SmartPlaylistRuleGroup[]
}

type PlaylistLike = Playlist | FavoriteList | RecentlyPlayedList

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
  type: 'users'
  id: number
  name: string
  email: string
  is_admin: boolean
  password?: string
  preferences?: UserPreferences
  avatar: string
}

interface Settings {
  media_path?: string
}

interface Interaction {
  type: 'interactions'
  readonly id: number
  readonly song_id: Song['id']
  liked: boolean
  play_count: number
}

interface EqualizerBandElement extends HTMLElement {
  noUiSlider: {
    destroy (): void
    on (eventName: 'change' | 'slide', handler: (value: string[], handle: number) => void): void
    set (options: number | any[]): void
  }

  isPreamp: boolean
}

type OverlayState = {
  dismissible: boolean
  type: 'loading' | 'success' | 'info' | 'warning' | 'error'
  message: string
}

interface SongRow {
  song: Song
  selected: boolean
}

interface EqualizerPreset {
  id: number
  name: string
  preamp: number
  gains: number[]
}

declare type PlaybackState = 'Stopped' | 'Playing' | 'Paused'
declare type ScreenName =
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
  | 'Genres'
  | 'Genre'
  | 'Playlist'
  | 'Upload'
  | 'Search.Excerpt'
  | 'Search.Songs'
  | '404'

declare type ArtistAlbumCardLayout = 'full' | 'compact'

interface AddToMenuConfig {
  queue: boolean
  favorites: boolean
}

interface SongListControlsConfig {
  play: boolean
  addTo: AddToMenuConfig
  clearQueue: boolean
  deletePlaylist: boolean
  refresh: boolean
}

type ThemeableProperty = '--color-text-primary'
  | '--color-text-secondary'
  | '--color-bg-primary'
  | '--color-bg-secondary'
  | '--color-highlight'
  | '--bg-image'
  | '--bg-position'
  | '--bg-attachment'
  | '--bg-size'

interface Theme {
  id: string
  name?: string
  thumbnailColor: string
  thumbnailUrl?: string
  selected?: boolean
  properties?: Partial<Record<ThemeableProperty, string>>
}

type ArtistAlbumViewMode = 'list' | 'thumbnails'

type RepeatMode = 'NO_REPEAT' | 'REPEAT_ALL' | 'REPEAT_ONE'

interface SongListConfig {
  sortable: boolean
  reorderable: boolean
}

type SongListSortField = keyof Pick<Song, 'track' | 'disc' | 'title' | 'album_name' | 'length' | 'artist_name'>

type SortOrder = 'asc' | 'desc'

type MethodOf<T> = { [K in keyof T]: T[K] extends Closure ? K : never; }[keyof T]

interface PaginatorResource {
  data: any[]
  links: {
    next: string | null
  }
  meta: {
    current_page: number
  }
}

type EditSongFormTabName = 'details' | 'lyrics'

type ToastMessage = {
  id: string
  type: 'info' | 'success' | 'warning' | 'danger'
  content: string
  timeout: number // seconds
}

type Genre = {
  type: 'genres'
  name: string
  song_count: number
  length: number
}

type ExtraPanelTab = 'Lyrics' | 'Artist' | 'Album' | 'YouTube'

type Visualizer = {
  init: (container: HTMLElement) => Promise<Closure>
  id: string
  name: string
  credits?: {
    author: string
    url: string
  }
}

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

type MaybeArray<T> = T | T[]

interface CompositeToken {
  'audio-token': string
  token: string
}

type SSOProvider = 'Google' | 'Reverse Proxy'

interface Window {
  BASE_URL: string
  MAILER_CONFIGURED: boolean
  IS_DEMO: boolean
  SSO_PROVIDERS: SSOProvider[]
  AUTH_TOKEN: CompositeToken | null

  readonly PUSHER_APP_KEY: string
  readonly PUSHER_APP_CLUSTER: string

  readonly MediaMetadata: Constructable<Record<string, any>>
  createLemonSqueezy?: () => Closure

  LemonSqueezy: {
    Url: {
      Open: (url: string) => void
    }
  }
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

interface Playable {
  type: 'songs' | 'episodes'
  readonly id: string
  title: string
  readonly length: number
  play_count_registered?: boolean
  play_count: number
  play_start_time?: number
  preloaded?: boolean
  playback_state?: PlaybackState
  liked: boolean
  fmt_length?: string
  created_at: string
}

interface Song extends Playable {
  type: 'songs'
  readonly owner_id: User['id'],
  album_id: Album['id']
  album_name: Album['name']
  album_cover: Album['cover']
  artist_id: Artist['id']
  artist_name: Artist['name']
  album_artist_id: Artist['id']
  album_artist_name: Artist['name']
  genre: string
  track: number | null
  disc: number | null
  year: number | null
  lyrics: string
  is_public: boolean
  deleted?: boolean
}

interface Episode extends Playable {
  type: 'episodes'
  episode_link: string | null
  episode_description: string
  episode_image: string
  podcast_id: string
  podcast_title: string
  podcast_author: string
}

interface CollaborativeSong extends Playable {
  collaboration: {
    user: PlaylistCollaborator
    added_at: string | null
    fmt_added_at: string | null
  }
}

interface QueueState {
  type: 'queue-states'
  songs: Playable[]
  current_song: Playable | null
  playback_position: number
}

interface SmartPlaylistRuleGroup {
  id: string
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
  id: string
  model: SmartPlaylistModel
  operator: SmartPlaylistOperator['operator']
  value: any[]
}

interface SerializedSmartPlaylistRule {
  id: string
  model: SmartPlaylistModel['name']
  operator: SmartPlaylistOperator['operator']
  value: any[]
}

type SmartPlaylistInputTypes = Record<SmartPlaylistModel['type'], SmartPlaylistOperator[]>

type FavoriteList = {
  name: 'Favorites'
  playables: Playable[]
}

type RecentlyPlayedList = {
  name: 'Recently Played'
  playables: Playable[]
}

interface PlaylistFolder {
  type: 'playlist-folders'
  readonly id: string
  name: string
  // we don't need to keep track of the playlists here, as they can be computed using their folder_id value
}

type PlaylistCollaborator = Pick<User, 'id' | 'name' | 'avatar'> & {
  type: 'playlist-collaborators'
}

interface Playlist {
  type: 'playlists'
  readonly id: string
  readonly user_id: User['id']
  name: string
  folder_id: PlaylistFolder['id'] | null
  is_smart: boolean
  is_collaborative: boolean
  rules: SmartPlaylistRuleGroup[]
  own_songs_only: boolean
  cover: string | null
  playables?: Playable[]
}

type PlaylistLike = Playlist | FavoriteList | RecentlyPlayedList

interface Podcast {
  readonly type: 'podcasts'
  readonly id: string
  readonly title: string
  readonly url: string
  readonly link: string
  readonly image: string
  readonly description: string
  readonly author: string
  readonly subscribed_at: string
  readonly last_played_at: string
  readonly state: {
    current_episode: Playable['id'] | null
    progresses: Record<Playable['id'], number>
  }
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

interface UserPreferences extends Record<string, any> {
  volume: number
  show_now_playing_notification: boolean
  repeat_mode: RepeatMode
  confirm_before_closing: boolean
  continuous_playback: boolean
  equalizer: EqualizerPreset,
  artists_view_mode: ArtistAlbumViewMode | null,
  albums_view_mode: ArtistAlbumViewMode | null,
  transcode_on_mobile: boolean
  transcode_quality: number
  support_bar_no_bugging: boolean
  show_album_art_overlay: boolean
  lyrics_zoom_level: number | null
  theme?: Theme['id'] | null
  visualizer?: Visualizer['id'] | null
  active_extra_panel_tab: ExtraPanelTab | null
  make_uploads_public: boolean
  lastfm_session_key?: string
}

interface User {
  type: 'users'
  id: number
  name: string
  email: string
  is_admin: boolean
  is_prospect: boolean
  password?: string
  preferences?: UserPreferences
  avatar: string
  sso_provider: SSOProvider | null
  sso_id: string | null
}

interface Settings {
  media_path?: string
}

interface Interaction {
  type: 'interactions'
  readonly id: number
  readonly song_id: Playable['id']
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

interface PlayableRow {
  playable: Playable
  selected: boolean
}

interface EqualizerPreset {
  name: string | null
  preamp: number
  gains: number[]
}

declare type PlaybackState = 'Stopped' | 'Playing' | 'Paused'
declare type ScreenName =
  | 'Home'
  | 'Default' | 'Blank'
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
  | 'Podcasts'
  | 'Podcast'
  | 'Episode'
  | 'Search.Excerpt'
  | 'Search.Songs'
  | 'Invitation.Accept'
  | 'Password.Reset'
  | '404'

declare type ArtistAlbumCardLayout = 'full' | 'compact'

interface AddToMenuConfig {
  queue: boolean
  favorites: boolean
}

interface SongListControlsConfig {
  addTo: AddToMenuConfig
  clearQueue: boolean
  deletePlaylist: boolean
  refresh: boolean
  filter: boolean
}

type ThemeableProperty = '--color-text-primary'
  | '--color-text-secondary'
  | '--color-bg-primary'
  | '--color-bg-secondary'
  | '--color-highlight'
  | '--color-bg-input'
  | '--color-text-input'
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

interface PlayableListConfig {
  filterable: boolean
  sortable: boolean
  reorderable: boolean
  collaborative: boolean
  hasCustomOrderSort: boolean
}

type PlayableListContext = {
  entity?: Playlist | Album | Artist | Genre,
  type?: Extract<ScreenName, 'Songs' | 'Album' | 'Artist' | 'Playlist' | 'Favorites' | 'RecentlyPlayed' | 'Queue' | 'Genre' | 'Search.Songs'>
}

type PlayableListSortField =
  keyof Pick<Song, 'track' | 'disc' | 'title' | 'album_name' | 'length' | 'artist_name' | 'created_at'>
  | keyof Pick<Episode, 'podcast_author' | 'podcast_title'>
  | 'position'

type PodcastListSortField =  keyof Pick<Podcast, 'title' | 'last_played_at' | 'subscribed_at' | 'author'>

type SortOrder = 'asc' | 'desc'
type MoveType = 'before' | 'after'

type MethodOf<T> = { [K in keyof T]: T[K] extends Closure ? K : never; }[keyof T]

interface PaginatorResource<T> {
  data: T[]
  links: {
    next: string | null
  }
  meta: {
    current_page: number
  }
}

type EditSongFormTabName = 'details' | 'lyrics' | 'visibility'

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

declare module '*.vue'
declare module '*.jpg'
declare module '*.png'
declare module '*.svg'

declare type Closure<T = unknown | any> = (...args: Array<unknown | any>) => T

declare module 'sketch-js' {
  function create (config: Record<string, any>): any
}

declare module 'youtube-player' {
  import type { YouTubePlayer } from 'youtube-player/dist/types'

  function createYouTubePlayer (name: string, options: Record<string, any>): YouTubePlayer

  export default createYouTubePlayer
}

interface Plyr {
  media: HTMLMediaElement

  restart: () => void

  play: () => void

  pause: () => void

  seek: (position: number) => void

  setVolume: (volume: number) => void
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
  'token': string
}

type SSOProvider = 'Google' | 'Reverse Proxy'

interface Window {
  BASE_URL: string
  MAILER_CONFIGURED: boolean
  IS_DEMO: boolean

  DEMO_ACCOUNT?: {
    email: string
    password: string
  }

  SSO_PROVIDERS: SSOProvider[]
  AUTH_TOKEN: CompositeToken | null
  ACCEPTED_AUDIO_EXTENSIONS: string[]

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

interface FileSystemEntry {
  createReader: () => FileSystemDirectoryReader
}

type EncyclopediaDisplayMode = 'aside' | 'full'
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
  type: 'artists'
  readonly id: string
  name: string
  image: string // empty string = no image
  created_at: string
  is_external: boolean
  favorite: boolean
}

interface Album {
  type: 'albums'
  readonly id: string
  artist_id: Artist['id']
  artist_name: Artist['name']
  name: string
  cover: string // empty string = no cover
  thumbnail?: string | null
  created_at: string
  year: number | null
  is_external: boolean
  favorite: boolean
}

interface IStreamable {
  readonly type: Song['type'] | Episode['type'] | RadioStation['type']
  readonly id: string
  favorite: boolean
  playback_state?: PlaybackState
  created_at: string
}

interface BasePlayable extends IStreamable {
  type: Song['type'] | Episode['type']
  title: string
  readonly length: number
  play_count_registered?: boolean
  play_count: number
  play_start_time?: number
  preloaded?: boolean
  playback_state?: PlaybackState
  fmt_length?: string
  embed_stream_url?: string // only when embedded
}

interface Song extends BasePlayable {
  type: 'songs'
  readonly owner_id: User['id']
  album_id: Album['id']
  album_name: Album['name']
  album_cover: Album['cover']
  artist_id: Artist['id']
  artist_name: Artist['name']
  album_artist_id: Artist['id']
  album_artist_name: Artist['name']
  genre: string
  track: number | null
  disc: number
  year: number | null
  lyrics: string
  is_public: boolean
  is_external: boolean
  basename?: string
  deleted?: boolean
  collaboration?: {
    user: PlaylistCollaborator
    added_at: string | null
    fmt_added_at: string | null
  }
}

interface Episode extends BasePlayable {
  type: 'episodes'
  episode_link: string | null
  episode_description: string
  episode_image: string
  podcast_id: string
  podcast_title: string
  podcast_author: string
}

interface RadioStation extends IStreamable {
  readonly type: 'radio-stations'
  name: string
  url: string
  logo: string | null
  description: string
  is_public: boolean
}

type Playable = Song | Episode
type Streamable = Playable | RadioStation
type Embeddable = Playable | Playlist | Artist | Album

interface Embed {
  type: 'embeds'
  id: string
  user_id: User['id']
  embeddable_id: Embeddable['id']
  embeddable_type: 'playable' | 'playlist' | 'artist' | 'album'
}

type WidgetReadyEmbed = Embed & {
  embeddable: Embeddable
  playables: Playable[]
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

interface FavoriteList {
  name: 'Favorites'
  playables: Playable[]
}

interface RecentlyPlayedList {
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
  readonly owner_id: User['id']
  name: string
  description: string
  folder_id: PlaylistFolder['id'] | null
  is_smart: boolean
  is_collaborative: boolean
  rules: SmartPlaylistRuleGroup[]
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
  favorite: boolean
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
  equalizer: EqualizerPreset
  albums_view_mode: ViewMode
  artists_view_mode: ViewMode
  radio_stations_view_mode: ViewMode
  albums_sort_field: AlbumListSortField
  artists_sort_field: ArtistListSortField
  genres_sort_field: GenreListSortField
  podcasts_sort_field: PodcastListSortField
  radio_stations_sort_field: RadioStationListSortField
  albums_sort_order: SortOrder
  artists_sort_order: SortOrder
  podcasts_sort_order: SortOrder
  genres_sort_order: SortOrder
  radio_stations_sort_order: SortOrder
  albums_favorites_only: boolean
  artists_favorites_only: boolean
  podcasts_favorites_only: boolean
  radio_stations_favorites_only: boolean
  transcode_on_mobile: boolean
  transcode_quality: number
  support_bar_no_bugging: boolean
  show_album_art_overlay: boolean
  lyrics_zoom_level: number | null
  theme?: Theme['id'] | null
  visualizer?: Visualizer['id'] | null
  active_extra_panel_tab: SideSheetTab | null
  make_uploads_public: boolean
  include_public_media: boolean
  lastfm_session_key?: string
}

type Permission = 'manage settings' | 'manage users' | 'manage songs' | 'manage podcasts' | 'manage radio stations'
type Role = ('admin' | 'manager' | 'user') & string

interface User {
  type: 'users'
  id: string
  name: string
  email: string
  is_prospect: boolean
  password?: string
  avatar: string
  role: Role
  sso_provider: SSOProvider | null
  sso_id: string | null
  preferences?: UserPreferences
  permissions?: Permission[]
}

type CurrentUser = User & {
  preferences: UserPreferences
  permissions: Permission[]
}

interface Settings {
  media_path?: string
}

interface Interaction {
  type: 'interactions'
  readonly id: number
  readonly song_id: Playable['id']
  play_count: number
}

interface Favorite {
  readonly type: 'favorites'
  readonly favoriteable_id: string
  readonly favoriteable_type: 'playable' | 'podcast' | 'album' | 'artist'
  readonly user_id: User['id']
  readonly created_at: string
}

interface EqualizerBandElement extends HTMLElement {
  noUiSlider: {
    destroy: () => void
    on: (eventName: 'change' | 'slide', handler: (value: string[], handle: number) => void) => void
    set: (options: number | any[]) => void
  }

  isPreamp: boolean
}

interface OverlayState {
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
  | '404'
  | 'Album'
  | 'Albums'
  | 'Artist'
  | 'Artists'
  | 'Default'
  | 'Embed'
  | 'Episode'
  | 'Favorites'
  | 'Genre'
  | 'Genres'
  | 'Home'
  | 'Invitation.Accept'
  | 'MediaBrowser'
  | 'Password.Reset'
  | 'Playlist'
  | 'Playlist.Collaborate'
  | 'Podcast'
  | 'Podcasts'
  | 'Profile'
  | 'Queue'
  | 'Radio.Stations'
  | 'RecentlyPlayed'
  | 'Search.Excerpt'
  | 'Search.Playables'
  | 'Settings'
  | 'Songs'
  | 'Upload'
  | 'Users'
  | 'Visualizer'
  | 'YouTube'

declare type CardLayout = 'full' | 'compact'

interface AddToMenuConfig {
  queue: boolean
  favorites: boolean
}

interface PlayableListControlsConfig {
  addTo: AddToMenuConfig
  clearQueue: boolean
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
  | '--font-family'
  | '--font-size'

interface Theme {
  id: string
  name: string
  thumbnailColor: string
  thumbnailUrl?: string
  selected?: boolean
  properties?: Partial<Record<ThemeableProperty, string>>
}

type ViewMode = 'list' | 'thumbnails'

type RepeatMode = 'NO_REPEAT' | 'REPEAT_ALL' | 'REPEAT_ONE'

interface PlayableListConfig {
  filterable: boolean
  sortable: boolean
  reorderable: boolean
  collaborative: boolean
  hasCustomOrderSort: boolean
  hasHeader: boolean
}

interface PlayableListContext {
  entity?: Playlist | Album | Artist | Genre
  type?: Extract<ScreenName, 'Home' | 'Songs' | 'Album' | 'Artist' | 'Playlist' | 'Favorites' | 'RecentlyPlayed' | 'Queue' | 'Genre' | 'Search.Playables'>
}

type PlayableListSortField =
  keyof Pick<Song, 'track' | 'disc' | 'title' | 'album_name' | 'length' | 'artist_name' | 'genre' | 'year' | 'created_at'>
  | keyof Pick<Episode, 'podcast_author' | 'podcast_title'>
  | 'position'

type AlbumListSortField = keyof Pick<Album, 'name' | 'year' | 'artist_name' | 'created_at'>
type ArtistListSortField = keyof Pick<Artist, 'name' | 'created_at'>
type GenreListSortField = keyof Pick<Genre, 'name' | 'song_count'>
type PodcastListSortField = keyof Pick<Podcast, 'title' | 'last_played_at' | 'subscribed_at' | 'author'>
type RadioStationListSortField = keyof Pick<RadioStation, 'name' | 'created_at'>
type SortField =
  PodcastListSortField
  | AlbumListSortField
  | ArtistListSortField
  | RadioStationListSortField
  | GenreListSortField

interface BasicListSorterDropDownItem<T extends SortField> {
  label: string
  field: T
}

type SortOrder = 'asc' | 'desc'
type Placement = 'before' | 'after'

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

interface ToastMessage {
  id: string
  type: 'info' | 'success' | 'warning' | 'danger'
  content: string
  timeout: number // seconds
}

interface Genre {
  type: 'genres'
  id: string
  name: string
  song_count: number
  length: number
}

type SideSheetTab = 'Lyrics' | 'Artist' | 'Album' | 'YouTube'

interface Visualizer {
  init: (container: HTMLElement) => Promise<Closure>
  id: string
  name: string
  credits?: {
    author: string
    url: string
  }
}

type PlayableListColumnName =
  'title'
  | 'album'
  | 'artist'
  | 'track'
  | 'duration'
  | 'created_at'
  | 'play_count'
  | 'year'
  | 'genre'

interface Folder {
  type: 'folders'
  id: string
  parent_id: string | null
  path: string
  name: string
}

interface MediaRow {
  item: Folder | Song
  selected: boolean
}

type MediaReference = Pick<Folder, 'type' | 'path'> | Pick<Song, 'type' | 'id'>

interface LiveEvent {
  type: 'live-events'
  id: string
  name: string
  dates: {
    start: string
    end: string | null
  }
  url: string
  image: string
  venue: {
    name: string
    url: string
    city: string
  }
}

interface EmbedLayout {
  id: string
  name: string
}

interface EmbedOptions {
  theme: Theme['id']
  layout: EmbedLayout['id']
  preview: boolean
}

declare module 'vue-virtual-scroller' {
  const VirtualScroller: any
  export { VirtualScroller }
}

declare module 'vue-global-events' {
  // @ts-ignore
  export default any
}

declare module 'alertify.js' {
  function alert(msg: string): void
  function confirm(msg: string, okFunc: Function, cancelFunc?: Function): void
  function success(msg: string, cb?: Function): void
  function error(msg: string, cb?: Function): void
  function log(msg: string, cb?: Function): void
  function logPosition(position: string): void
  function closeLogOnClick(close: boolean): void
}

declare module 'select' {
  function select(el: HTMLElement): void
  export default select
}

declare module 'sketch-js' {
  function create(o: { [key: string]: any }): any
}

declare module 'youtube-player' {
  import { YouTubePlayer } from 'youtube-player/dist/types'

  function createYouTubePlayer(name: string, options: { [propName: string]: any }): YouTubePlayer
  export default createYouTubePlayer
}

interface Plyr {
  media: HTMLMediaElement
  restart(): void
  play(): void
  pause(): void
  seek(position: number): void
  setVolume(volume: number): void
}

declare module 'plyr' {
  function setup(el: HTMLMediaElement | HTMLMediaElement[], options: object): Plyr[]
}

declare module 'ismobilejs' {
  let apple: { device: boolean }
  let any: boolean
  let phone: boolean
}

declare module 'nouislider' {
  function create(el: HTMLElement, config: {
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

declare const KOEL_ENV: 'app' | 'web'
declare const NODE_ENV: 'dev' | 'test' | 'prod' | 'demo'

declare module '*.vue' {
  import { defineComponent } from 'vue'
  const Component: ReturnType<typeof defineComponent>
  export default Component
}

interface Constructable<T> {
  new(...args: any): T
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
  readonly MediaMetadata: Constructable<object>
}

interface FileSystemDirectoryReader {
  readEntries(successCallback: Function, errorCallback?: Function): FileSystemEntry[]
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

interface AlbumInfo {
  image: string | null
  readonly tracks: AlbumTrack[]
  wiki?: {
    summary: string
    full: string
  }
  url?: string
}

interface AlbumTrack {
  readonly title: string
  readonly length: number
  fmtLength: string
}

interface ArtistInfo {
  image: string | null
  bio?: {
    summary: string
    full: string
  }
  url?: string
}

interface SmartPlaylistRule {
  id: number
  model: SmartPlaylistModel
  operator: string
  value: any[]
}

interface SerializedSmartPlaylistRule {
  id: number
  model: string
  operator: string
  value: any[]
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

interface SmartPlaylistTypes {
  [propName: string]: any[]
}

interface SmartPlaylistOperator {
  operator: string
  label: string
  type?: string
  unit?: string
  inputs?: number
}

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

interface User {
  id: number
  name: string
  email: string
  password: string
  is_admin: boolean
  preferences: { [key: string]: any }
  avatar: string
}

interface Settings extends Object {
  media_path?: string
}

interface Interaction {
  readonly song_id: string
  liked: boolean
  play_count: number
}

interface SongListState {
  songs: Song[]
  [propName: string]: any
}

interface SongListMeta {
  songCount: number
  totalLength: string
}

declare module 'koel/types/ui' {
  import { Component } from 'vue'

  export type BaseContextMenu = Component & {
    open(y: number, x: number): void
    close(): void
  }

  export type BasePlaylistMenu = Component & {
    open(top: number, left: number): void
    close(): void
  }

  export type SongListComponent = Component & {
    rowClicked(songItem: Component, event: MouseEvent): void
    openContextMenu(songItem: Component, event: MouseEvent): void
    removeDroppableState(event: DragEvent): void
    handleDrop(songItem: Component, event: DragEvent): void
    allowDrop(event: DragEvent): void
    dragStart(songItem: Component, event: DragEvent): void
  }

  export type SongListRowComponent = Component & {
    item: SongProxy
  }

  export interface TypeAheadConfig {
    displayKey: string
    filterKey: string
    name: string
  }

  interface SliderElement extends HTMLElement {
    noUiSlider?: {
      destroy(): void
      on(eventName: 'change' | 'slide', handler: Function): void
      set(options: number | any[]): void
    }
  }
}

interface SongProxy {
  song: Song
  selected: boolean
}

interface EqualizerPreset {
  id: number
  name: string
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

type Theme = {
  id: string
  name?: string
  thumbnailColor: string
  thumbnailUrl?: string
  selected?: boolean
}

type ArtistAlbumViewMode = 'list' | 'thumbnails'

type RepeatMode = 'NO_REPEAT' | 'REPEAT_ALL' | 'REPEAT_ONE'

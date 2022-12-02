import { Ref } from 'vue'

export interface Events {
  LOG_OUT: () => void
  TOGGLE_SIDEBAR: () => void
  FOCUS_SEARCH_FIELD: () => void
  PLAY_YOUTUBE_VIDEO: (payload: { id: string, title: string }) => void
  SEARCH_KEYWORDS_CHANGED: (keywords: string) => void

  SONG_CONTEXT_MENU_REQUESTED: (event: MouseEvent, songs: Song | Song[]) => void
  ALBUM_CONTEXT_MENU_REQUESTED: (event: MouseEvent, album: Album) => void
  ARTIST_CONTEXT_MENU_REQUESTED: (event: MouseEvent, artist: Artist) => void
  CREATE_NEW_PLAYLIST_CONTEXT_MENU_REQUESTED: (event: MouseEvent) => void
  PLAYLIST_CONTEXT_MENU_REQUESTED: (event: MouseEvent, playlist: Playlist) => void
  PLAYLIST_FOLDER_CONTEXT_MENU_REQUESTED: (event: MouseEvent, playlistFolder: PlaylistFolder) => void
  CONTEXT_MENU_OPENED: (el: Ref<HTMLElement> | HTMLElement) => void

  MODAL_SHOW_ADD_USER_FORM: () => void
  MODAL_SHOW_EDIT_USER_FORM: (user: User) => void
  MODAL_SHOW_EDIT_SONG_FORM: (songs: Song | Song[], initialTab?: EditSongFormTabName) => void
  MODAL_SHOW_CREATE_PLAYLIST_FORM: (folder: PlaylistFolder | null) => void
  MODAL_SHOW_EDIT_PLAYLIST_FORM: (playlist: Playlist) => void
  MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM: (folder: PlaylistFolder | null) => void
  MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM: () => void
  MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM: (playlistFolder: PlaylistFolder) => void
  MODAL_SHOW_ABOUT_KOEL: () => void
  MODAL_SHOW_EQUALIZER: () => void

  PLAYLIST_DELETE: (playlist: Playlist) => void
  PLAYLIST_FOLDER_DELETE: (playlistFolder: PlaylistFolder) => void
  PLAYLIST_SONGS_REMOVED: (playlist: Playlist, songs: Song[]) => void
  PLAYLIST_UPDATED: (playlist: Playlist) => void

  SONGS_UPDATED: () => void
  SONGS_DELETED: (songs: Song[]) => void
  SONG_QUEUED_FROM_ROUTE: (songId: string) => void

  SOCKET_TOGGLE_PLAYBACK: () => void
  SOCKET_TOGGLE_FAVORITE: () => void
  SOCKET_PLAY_NEXT: () => void
  SOCKET_PLAY_PREV: () => void
  SOCKET_PLAYBACK_STOPPED: () => void
  SOCKET_GET_STATUS: () => void
  SOCKET_STATUS: (data: { song?: Song, volume: number }) => void
  SOCKET_GET_CURRENT_SONG: () => void
  SOCKET_SONG: (song: Song) => void
  SOCKET_SET_VOLUME: (volume: number) => void
  SOCKET_VOLUME_CHANGED: (volume: number) => void
}

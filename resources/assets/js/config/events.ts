import { Ref } from 'vue'
import { SongUpdateResult } from '@/stores'

export interface Events {
  LOG_OUT: () => void
  TOGGLE_SIDEBAR: () => void
  FOCUS_SEARCH_FIELD: () => void
  PLAY_YOUTUBE_VIDEO: (payload: { id: string, title: string }) => void
  SEARCH_KEYWORDS_CHANGED: (keywords: string) => void

  PLAYABLE_CONTEXT_MENU_REQUESTED: (event: MouseEvent, playables: MaybeArray<Playable>) => void
  ALBUM_CONTEXT_MENU_REQUESTED: (event: MouseEvent, album: Album) => void
  ARTIST_CONTEXT_MENU_REQUESTED: (event: MouseEvent, artist: Artist) => void
  PODCAST_CONTEXT_MENU_REQUESTED: (event: MouseEvent, podcast: Podcast) => void
  CREATE_NEW_PLAYLIST_CONTEXT_MENU_REQUESTED: ({ top, left }: { top: number, left: number }) => void
  PLAYLIST_CONTEXT_MENU_REQUESTED: (event: MouseEvent, playlist: Playlist) => void
  PLAYLIST_FOLDER_CONTEXT_MENU_REQUESTED: (event: MouseEvent, playlistFolder: PlaylistFolder) => void
  CONTEXT_MENU_OPENED: (el: Ref<HTMLElement> | HTMLElement) => void

  FULLSCREEN_TOGGLE: () => void

  MODAL_SHOW_ADD_USER_FORM: () => void
  MODAL_SHOW_INVITE_USER_FORM: () => void
  MODAL_SHOW_EDIT_USER_FORM: (user: User) => void
  MODAL_SHOW_EDIT_SONG_FORM: (songs: MaybeArray<Song>, initialTab?: EditSongFormTabName) => void
  MODAL_SHOW_CREATE_PLAYLIST_FORM: (folder?: PlaylistFolder | null, playables?: MaybeArray<Playable>) => void
  MODAL_SHOW_EDIT_PLAYLIST_FORM: (playlist: Playlist) => void
  MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM: (folder?: PlaylistFolder | null) => void
  MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM: () => void
  MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM: (playlistFolder: PlaylistFolder) => void
  MODAL_SHOW_PLAYLIST_COLLABORATION: (playlist: Playlist) => void
  MODAL_SHOW_ADD_PODCAST_FORM: () => void,
  MODAL_SHOW_ABOUT_KOEL: () => void
  MODAL_SHOW_KOEL_PLUS: () => void
  MODAL_SHOW_EQUALIZER: () => void

  PLAYLIST_DELETE: (playlist: Playlist) => void
  PLAYLIST_FOLDER_DELETE: (playlistFolder: PlaylistFolder) => void
  PLAYLIST_CONTENT_REMOVED: (playlist: Playlist, playables: Playable[]) => void
  PLAYLIST_UPDATED: (playlist: Playlist) => void
  PLAYLIST_COLLABORATOR_REMOVED: (playlist: Playlist) => void

  SONGS_UPDATED: (result: SongUpdateResult) => void
  SONGS_DELETED: (songs: Song[]) => void
  SONG_UPLOADED: (song: Song) => void

  EPISODE_PROGRESS_UPDATED: (episode: Episode, progress: number) => void

  SOCKET_TOGGLE_PLAYBACK: () => void
  SOCKET_TOGGLE_FAVORITE: () => void
  SOCKET_PLAY_NEXT: () => void
  SOCKET_PLAY_PREV: () => void
  SOCKET_PLAYBACK_STOPPED: () => void
  SOCKET_GET_STATUS: () => void
  SOCKET_STATUS: (data: { playable?: Playable, volume: number }) => void
  SOCKET_GET_CURRENT_PLAYABLE: () => void
  SOCKET_PLAYABLE: (playable: Playable) => void
  SOCKET_SET_VOLUME: (volume: number) => void
  SOCKET_VOLUME_CHANGED: (volume: number) => void
}

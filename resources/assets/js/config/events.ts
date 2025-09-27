import type { SongUpdateResult } from '@/stores/playableStore'

export interface Events {
  LOG_OUT: () => void
  TOGGLE_SIDEBAR: () => void
  FOCUS_SEARCH_FIELD: () => void
  PLAY_YOUTUBE_VIDEO: (payload: { id: string, title: string }) => void
  SEARCH_KEYWORDS_CHANGED: (keywords: string) => void

  FULLSCREEN_TOGGLE: () => void
  PLAYBACK_STARTED: (playable: Playable) => void
  UP_NEXT: (playable: Playable | null) => void

  MODAL_SHOW_ADD_USER_FORM: () => void
  MODAL_SHOW_INVITE_USER_FORM: () => void
  MODAL_SHOW_EDIT_USER_FORM: (user: User) => void
  MODAL_SHOW_EDIT_SONG_FORM: (songs: MaybeArray<Song>, initialTab?: EditSongFormTabName) => void
  MODAL_SHOW_CREATE_PLAYLIST_FORM: (folder?: PlaylistFolder | null, playables?: MaybeArray<Playable>) => void
  MODAL_SHOW_EDIT_PLAYLIST_FORM: (playlist: Playlist) => void
  MODAL_SHOW_EDIT_ALBUM_FORM: (album: Album) => void
  MODAL_SHOW_EDIT_ARTIST_FORM: (artist: Artist) => void
  MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM: (folder?: PlaylistFolder | null) => void
  MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM: () => void
  MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM: (playlistFolder: PlaylistFolder) => void
  MODAL_SHOW_PLAYLIST_COLLABORATION: (playlist: Playlist) => void
  MODAL_SHOW_ADD_PODCAST_FORM: () => void
  MODAL_SHOW_ADD_RADIO_STATION_FORM: () => void
  MODAL_SHOW_EDIT_RADIO_STATION_FORM: (station: RadioStation) => void
  MODAL_SHOW_ABOUT_KOEL: () => void
  MODAL_SHOW_KOEL_PLUS: () => void
  MODAL_SHOW_EQUALIZER: () => void
  MODAL_SHOW_CREATE_EMBED_FORM: (embeddable: Embeddable) => void

  PLAYLIST_DELETED: (playlist: Playlist) => void
  PLAYLIST_CONTENT_REMOVED: (playlist: Playlist, playables: Playable[]) => void
  PLAYLIST_UPDATED: (playlist: Playlist) => void
  PLAYLIST_COLLABORATOR_REMOVED: (playlist: Playlist) => void

  PODCAST_UNSUBSCRIBED: (podcast: Podcast) => void

  SONGS_UPDATED: (result: SongUpdateResult) => void
  SONGS_DELETED: (songs: Song[]) => void
  SONG_UPLOADED: (song: Song) => void

  EPISODE_PROGRESS_UPDATED: (episode: Episode, progress: number) => void

  SOCKET_TOGGLE_PLAYBACK: () => void
  SOCKET_TOGGLE_FAVORITE: (streamable: Streamable) => void
  SOCKET_PLAY_NEXT: () => void
  SOCKET_PLAY_PREV: () => void
  SOCKET_PLAYBACK_STOPPED: () => void
  SOCKET_GET_STATUS: () => void
  SOCKET_STATUS: (data: { streamable?: Streamable, volume: number }) => void
  SOCKET_GET_CURRENT_PLAYABLE: () => void
  SOCKET_STREAMABLE: (streamable: Streamable) => void
  SOCKET_SET_VOLUME: (volume: number) => void
  SOCKET_VOLUME_CHANGED: (volume: number) => void
}

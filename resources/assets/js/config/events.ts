import type { SongUpdateResult } from '@/stores/playableStore'

export interface Events {
  LOG_OUT: () => void
  TOGGLE_SIDEBAR: () => void
  FOCUS_SEARCH_FIELD: () => void
  PLAY_YOUTUBE_VIDEO: (payload: { id: string; title: string }) => void
  SEARCH_KEYWORDS_CHANGED: (keywords: string) => void

  FULLSCREEN_TOGGLE: () => void
  PLAYBACK_STARTED: (playable: Playable) => void
  UP_NEXT: (playable: Playable | null) => void

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
  SOCKET_STATUS: (data: { streamable?: Streamable; volume: number }) => void
  SOCKET_GET_CURRENT_PLAYABLE: () => void
  SOCKET_STREAMABLE: (streamable: Streamable) => void
  SOCKET_SET_VOLUME: (volume: number) => void
  SOCKET_VOLUME_CHANGED: (volume: number) => void
}

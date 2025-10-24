export interface ContextMenus {
  ALBUM: { album: Album }
  ARTIST: { artist: Artist }
  GENRE: { genre: Genre }
  MEDIA_BROWSER: { items: MaybeArray<Song | Folder> }
  PLAYABLES: { playables: Playable[] }
  PLAYLIST: { playlist: Playlist }
  PLAYLIST_FOLDER: { folder: PlaylistFolder }
  PODCAST: { podcast: Podcast }
  RADIO_STATION: { station: RadioStation }
  USER: { user: User }
  THEME: { theme: Theme }
}

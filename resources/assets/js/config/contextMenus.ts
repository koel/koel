export interface ContextMenus {
  ALBUM: { album: Album }
  ARTIST: { artist: Artist }
  FAVORITES: { playables: Playable[] }
  GENRE: { genre: Genre }
  MEDIA_BROWSER: { items: MaybeArray<Song | Folder> }
  PLAYABLES: { playables: Playable[] }
  PLAYLIST: { playlist: Playlist }
  PLAYLIST_FOLDER: { folder: PlaylistFolder }
  PODCAST: { podcast: Podcast }
  RADIO_STATION: { station: RadioStation }
  SELECTED_ALBUMS: { albums: Album[] }
  SELECTED_ARTISTS: { artists: Artist[] }
  SELECTED_RADIO_STATIONS: { stations: RadioStation[] }
  USER: { user: User }
  THEME: { theme: Theme }
}

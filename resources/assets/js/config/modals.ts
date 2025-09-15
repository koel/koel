export interface Modals {
  ABOUT_KOEL: never
  ADD_PODCAST_FORM: never
  ADD_RADIO_STATION_FORM: never
  ADD_USER_FORM: never
  CREATE_EMBED_FORM: { embeddable: Embeddable }
  CREATE_PLAYLIST_FORM: { folder: PlaylistFolder | null, playables: Playable[] }
  CREATE_PLAYLIST_FOLDER_FORM: never
  CREATE_SMART_PLAYLIST_FORM: { folder: PlaylistFolder | null }
  EDIT_ALBUM_FORM: { album: Album }
  EDIT_ARTIST_FORM: { artist: Artist }
  EDIT_PLAYLIST_FORM: { playlist: Playlist }
  EDIT_PLAYLIST_FOLDER_FORM: { folder: PlaylistFolder }
  EDIT_RADIO_STATION_FORM: { station: RadioStation }
  EDIT_SMART_PLAYLIST_FORM: { playlist: Playlist }
  EDIT_SONG_FORM: { songs: Song[], initialTab: EditSongFormTabName }
  EDIT_USER_FORM: { user: User }
  EQUALIZER: never
  INVITE_USER_FORM: never
  KOEL_PLUS: never
  PLAYLIST_COLLABORATION: { playlist: Playlist }
}

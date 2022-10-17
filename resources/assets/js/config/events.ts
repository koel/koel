export type EventName =
  'KOEL_READY'
  | 'LOG_OUT'
  | 'TOGGLE_SIDEBAR'
  | 'SHOW_OVERLAY'
  | 'HIDE_OVERLAY'
  | 'FOCUS_SEARCH_FIELD'
  | 'PLAY_YOUTUBE_VIDEO'
  | 'INIT_EQUALIZER'
  | 'TOGGLE_VISUALIZER'
  | 'SEARCH_KEYWORDS_CHANGED'

  | 'SONG_CONTEXT_MENU_REQUESTED'
  | 'ALBUM_CONTEXT_MENU_REQUESTED'
  | 'ARTIST_CONTEXT_MENU_REQUESTED'
  | 'CREATE_NEW_PLAYLIST_CONTEXT_MENU_REQUESTED'
  | 'PLAYLIST_CONTEXT_MENU_REQUESTED'
  | 'PLAYLIST_FOLDER_CONTEXT_MENU_REQUESTED'
  | 'CONTEXT_MENU_OPENED'

  | 'MODAL_SHOW_ADD_USER_FORM'
  | 'MODAL_SHOW_EDIT_USER_FORM'
  | 'MODAL_SHOW_EDIT_SONG_FORM'
  | 'MODAL_SHOW_CREATE_PLAYLIST_FORM'
  | 'MODAL_SHOW_EDIT_PLAYLIST_FORM'
  | 'MODAL_SHOW_CREATE_SMART_PLAYLIST_FORM'
  | 'MODAL_SHOW_CREATE_PLAYLIST_FOLDER_FORM'
  | 'MODAL_SHOW_EDIT_PLAYLIST_FOLDER_FORM'
  | 'MODAL_SHOW_ABOUT_KOEL'

  | 'PLAYLIST_DELETE'
  | 'PLAYLIST_FOLDER_DELETE'
  | 'SMART_PLAYLIST_UPDATED'
  | 'SONGS_UPDATED'
  | 'SONGS_DELETED'
  | 'SONG_QUEUED_FROM_ROUTE'

  // socket events
  | 'SOCKET_TOGGLE_PLAYBACK'
  | 'SOCKET_TOGGLE_FAVORITE'
  | 'SOCKET_PLAY_NEXT'
  | 'SOCKET_PLAY_PREV'
  | 'SOCKET_PLAYBACK_STOPPED'
  | 'SOCKET_GET_STATUS'
  | 'SOCKET_STATUS'
  | 'SOCKET_GET_CURRENT_SONG'
  | 'SOCKET_SONG'
  | 'SOCKET_SET_VOLUME'
  | 'SOCKET_VOLUME_CHANGED'

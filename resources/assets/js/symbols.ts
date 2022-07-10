import { InjectionKey, Ref } from 'vue'

export const SongListTypeKey: InjectionKey<SongListType> = Symbol('SongListType')
export const SongsKey: InjectionKey<Ref<Song[]>> = Symbol('Songs')
export const SelectedSongsKey: InjectionKey<Ref<Song[]>> = Symbol('SelectedSongs')
export const SongListConfigKey: InjectionKey<Partial<SongListConfig>> = Symbol('SongListConfig')
export const SongListSortFieldKey: InjectionKey<Ref<SongListSortField>> = Symbol('SongListSortField')
export const SongListSortOrderKey: InjectionKey<Ref<SortOrder>> = Symbol('SongListSortOrder')

export const EditSongFormInitialTabKey: InjectionKey<Ref<EditSongFormTabName>> = Symbol('EditSongFormInitialTab')

export const PlaylistKey: InjectionKey<Ref<Playlist>> = Symbol('Playlist')
export const UserKey: InjectionKey<Ref<User>> = Symbol('User')

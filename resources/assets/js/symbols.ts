import { DeepReadonly, InjectionKey, Ref } from 'vue'

export type ReadonlyInjectionKey<T> = InjectionKey<[Readonly<T> | DeepReadonly<T>, Closure]>

export const SongListTypeKey: ReadonlyInjectionKey<SongListType> = Symbol('SongListType')
export const SongsKey: ReadonlyInjectionKey<Ref<Song[]>> = Symbol('Songs')
export const SelectedSongsKey: ReadonlyInjectionKey<Ref<Song[]>> = Symbol('SelectedSongs')
export const SongListConfigKey: ReadonlyInjectionKey<Partial<SongListConfig>> = Symbol('SongListConfig')
export const SongListSortFieldKey: ReadonlyInjectionKey<Ref<SongListSortField>> = Symbol('SongListSortField')
export const SongListSortOrderKey: ReadonlyInjectionKey<Ref<SortOrder>> = Symbol('SongListSortOrder')

export const EditSongFormInitialTabKey: ReadonlyInjectionKey<Ref<EditSongFormTabName>> = Symbol('EditSongFormInitialTab')

export const PlaylistKey: ReadonlyInjectionKey<Ref<Playlist>> = Symbol('Playlist')
export const UserKey: ReadonlyInjectionKey<Ref<User>> = Symbol('User')

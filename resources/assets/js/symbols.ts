import { DeepReadonly, InjectionKey, Ref } from 'vue'
import Overlay from '@/components/ui/Overlay.vue'
import DialogBox from '@/components/ui/DialogBox.vue'
import MessageToaster from '@/components/ui/message-toaster/MessageToaster.vue'
import Router from '@/router'

export type ReadonlyInjectionKey<T> = InjectionKey<[Readonly<T> | DeepReadonly<T>, Closure]>

export const RouterKey: InjectionKey<Router> = Symbol('Router')
export const OverlayKey: InjectionKey<Ref<InstanceType<typeof Overlay>>> = Symbol('Overlay')
export const DialogBoxKey: InjectionKey<Ref<InstanceType<typeof DialogBox>>> = Symbol('DialogBox')
export const MessageToasterKey: InjectionKey<Ref<InstanceType<typeof MessageToaster>>> = Symbol('MessageToaster')

export const SongsKey: ReadonlyInjectionKey<Ref<Song[]>> | InjectionKey<Ref<Song[]>> = Symbol('Songs')
export const CurrentSongKey: InjectionKey<Ref<Song | undefined>> = Symbol('CurrentSong')
export const SelectedSongsKey: ReadonlyInjectionKey<Ref<Song[]>> = Symbol('SelectedSongs')
export const SongListConfigKey: ReadonlyInjectionKey<Partial<SongListConfig>> = Symbol('SongListConfig')
export const SongListSortFieldKey: ReadonlyInjectionKey<Ref<SongListSortField>> = Symbol('SongListSortField')
export const SongListSortOrderKey: ReadonlyInjectionKey<Ref<SortOrder>> = Symbol('SongListSortOrder')
export const SongListFilterKeywordsKey: InjectionKey<Ref<string>> = Symbol('SongListFilterKeywords')
export const SongListContextKey: InjectionKey<Ref<SongListContext>> = Symbol('SongListContext')

export const ModalContextKey: InjectionKey<Ref<Record<string, any>>> = Symbol('ModalContext')

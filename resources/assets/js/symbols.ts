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

export const PlayablesKey: ReadonlyInjectionKey<Ref<Playable[]>> | InjectionKey<Ref<Playable[]>> = Symbol('Playables')
export const CurrentPlayableKey: InjectionKey<Ref<Playable | undefined>> = Symbol('CurrentPlayable')
export const SelectedPlayablesKey: ReadonlyInjectionKey<Ref<Playable[]>> = Symbol('SelectedPlayables')
export const PlayableListConfigKey: ReadonlyInjectionKey<Partial<PlayableListConfig>> = Symbol('SongListConfig')
export const PlayableListSortFieldKey: ReadonlyInjectionKey<Ref<PlayableListSortField>> = Symbol('SongListSortField')
export const SongListSortOrderKey: ReadonlyInjectionKey<Ref<SortOrder>> = Symbol('SongListSortOrder')
export const SongListFilterKeywordsKey: InjectionKey<Ref<string>> = Symbol('SongListFilterKeywords')
export const PlayableListContextKey: InjectionKey<Ref<PlayableListContext>> = Symbol('SongListContext')

export const ModalContextKey: InjectionKey<Ref<Record<string, any>>> = Symbol('ModalContext')

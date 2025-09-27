import type { Component, DeepReadonly, InjectionKey, Ref } from 'vue'
import type Overlay from '@/components/ui/Overlay.vue'
import type DialogBox from '@/components/ui/DialogBox.vue'
import type MessageToaster from '@/components/ui/message-toaster/MessageToaster.vue'
import type Router from '@/router'

export type ReadonlyInjectionKey<T> = InjectionKey<[Readonly<T> | DeepReadonly<T>, Closure]>

export const RouterKey: InjectionKey<Router> = Symbol('Router')
export const OverlayKey: InjectionKey<Ref<InstanceType<typeof Overlay>>> = Symbol('Overlay')
export const DialogBoxKey: InjectionKey<Ref<InstanceType<typeof DialogBox>>> = Symbol('DialogBox')
export const MessageToasterKey: InjectionKey<Ref<InstanceType<typeof MessageToaster>>> = Symbol('MessageToaster')
export const ContextMenuKey: InjectionKey<Ref<{
  component: Component | null
  position: { top: number, left: number }
  props?: Record<string, any>
}>> = Symbol('ContextMenu')

export const FilterKeywordsKey: InjectionKey<Ref<string>> = Symbol('PlayableListFilterKeywords')

export const PlayablesKey: ReadonlyInjectionKey<Ref<Playable[]>> | InjectionKey<Ref<Playable[]>> = Symbol('PlayablesKey')
export const FilteredPlayablesKey: ReadonlyInjectionKey<Ref<Playable[]>> | InjectionKey<Ref<Playable[]>> = Symbol('FilteredPlayablesKey')
export const CurrentStreamableKey: InjectionKey<Ref<Streamable | undefined>> = Symbol('CurrentStreamableKey')
export const SelectedPlayablesKey: ReadonlyInjectionKey<Ref<Playable[]>> = Symbol('SelectedPlayables')
export const PlayableListConfigKey: ReadonlyInjectionKey<Partial<PlayableListConfig>> = Symbol('PlayableListConfig')
export const PlayableListSortFieldKey: ReadonlyInjectionKey<Ref<PlayableListSortField>> = Symbol('PlayableListSortField')
export const PlayableListSortOrderKey: ReadonlyInjectionKey<Ref<SortOrder>> = Symbol('PlayableListSortOrder')
export const PlayableListContextKey: InjectionKey<Ref<PlayableListContext>> = Symbol('PlayableListContext')

export const ModalContextKey: InjectionKey<Ref<Record<string, any>>> = Symbol('ModalContext')

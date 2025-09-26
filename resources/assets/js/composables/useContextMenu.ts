import type { Component } from 'vue'
import ContextMenu from '@/components/ui/context-menu/ContextMenu.vue'
import Separator from '@/components/ui/context-menu/ContextMenuSeparator.vue'
import MenuItem from '@/components/ui/context-menu/ContextMenuItem.vue'
import { ContextMenuKey } from '@/symbols'
import { requireInjection } from '@/utils/helpers'
import type { ContextMenus } from '@/config/contextMenus'

type Position = {
  top: number
  left: number
} | MouseEvent

export const useContextMenu = () => {
  const contextMenuOptions = requireInjection(ContextMenuKey)

  const openContextMenu = <K extends keyof ContextMenus = never> (
    menu: Component,
    position: Position,
    props?: K extends keyof ContextMenus
      ? ContextMenus[K] extends never
        ? Record<string, never>
        : ContextMenus[K]
      : Record<string, never>,
  ) => {
    if (position instanceof MouseEvent) {
      position = {
        top: position.clientY,
        left: position.clientX,
      }
    }

    contextMenuOptions.value = {
      component: menu,
      position,
      props: props || {},
    }
  }

  const closeContextMenu = () => {
    contextMenuOptions.value = {
      component: null,
      position: { top: 0, left: 0 },
    }
  }

  const trigger = (func: Closure) => {
    closeContextMenu()
    func()
  }

  return {
    ContextMenu,
    Separator,
    MenuItem,
    openContextMenu,
    closeContextMenu,
    trigger,
  }
}

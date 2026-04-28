<template>
  <div
    ref="el"
    v-koel-focus
    :class="extraClass"
    class="menu context-menu select-none shadow overflow-visible fixed inset-auto m-0"
    popover="manual"
    role="menu"
    tabindex="0"
    style="left: -9999px; top: -9999px"
    @contextmenu.prevent
    @keydown="onKeyDown"
  >
    <component :is="options.component" v-if="options.component" v-bind="options.props" />
  </div>
</template>

<script lang="ts" setup>
import { nextTick, onBeforeUnmount, ref, toRefs, watch } from 'vue'
import { computePosition, flip, shift } from '@floating-ui/dom'
import { logger } from '@/utils/logger'
import { requireInjection } from '@/utils/helpers'
import { ContextMenuKey } from '@/config/symbols'

const props = defineProps<{ extraClass?: string }>()
const { extraClass } = toRefs(props)

const options = requireInjection(ContextMenuKey)

const el = ref<HTMLElement>()

const positionAt = async (clientX: number, clientY: number) => {
  if (!el.value) {
    return
  }
  const virtualAnchor = {
    getBoundingClientRect: () => ({
      x: clientX,
      y: clientY,
      width: 0,
      height: 0,
      top: clientY,
      bottom: clientY,
      left: clientX,
      right: clientX,
    }),
  }
  const { x, y } = await computePosition(virtualAnchor, el.value, {
    placement: 'bottom-start',
    middleware: [flip(), shift({ padding: 8 })],
    strategy: 'fixed',
  })
  el.value.style.left = `${x}px`
  el.value.style.top = `${y}px`
}

const positionSubmenu = async (parent: HTMLElement, submenu: HTMLElement) => {
  const { x, y } = await computePosition(parent, submenu, {
    placement: 'right-start',
    middleware: [flip(), shift({ padding: 8 })],
    strategy: 'absolute',
  })
  // Resolve coords relative to the offset parent (the parent <li>).
  const offsetParent = submenu.offsetParent as HTMLElement | null
  if (offsetParent) {
    const parentRect = parent.getBoundingClientRect()
    const offsetRect = offsetParent.getBoundingClientRect()
    submenu.style.left = `${x - (parentRect.left - offsetRect.left)}px`
    submenu.style.top = `${y - (parentRect.top - offsetRect.top)}px`
  } else {
    submenu.style.left = `${x}px`
    submenu.style.top = `${y}px`
  }
}

type MenuItem = HTMLElement & {
  eventsRegistered?: boolean
  hideTimeout?: ReturnType<typeof setTimeout>
}

const HIDE_DELAY = 150

const hideSubmenu = (item: MenuItem, submenu: HTMLElement) => {
  submenu.removeAttribute('data-open')
  submenu.removeAttribute('style')
}

const scheduleHide = (item: MenuItem, submenu: HTMLElement) => {
  clearTimeout(item.hideTimeout)
  item.hideTimeout = setTimeout(() => hideSubmenu(item, submenu), HIDE_DELAY)
}

const cancelHide = (item: MenuItem) => {
  clearTimeout(item.hideTimeout)
  item.hideTimeout = undefined
}

const closeSiblingSubmenus = (item: MenuItem) => {
  const parent = item.parentElement

  if (!parent) {
    return
  }

  parent.querySelectorAll<HTMLElement>(':scope > li.has-sub').forEach((sibling: MenuItem) => {
    if (sibling === item) {
      return
    }

    const siblingSubmenu = sibling.querySelector<HTMLElement>('.submenu')

    if (siblingSubmenu) {
      cancelHide(sibling)
      hideSubmenu(sibling, siblingSubmenu)
    }
  })
}

const showSubmenu = async (item: MenuItem, submenu: HTMLElement) => {
  cancelHide(item)
  closeSiblingSubmenus(item)

  submenu.removeAttribute('style')
  submenu.setAttribute('data-open', '')

  await nextTick()
  await positionSubmenu(item, submenu)
}

const getMenuItems = (container: HTMLElement): HTMLElement[] =>
  Array.from(container.querySelectorAll<HTMLElement>('li:not(.separator)')).filter((li: HTMLElement) => {
    const closestSubmenu = li.closest('.submenu')
    return !closestSubmenu || closestSubmenu === container
  })

const getFocusedItem = (): HTMLElement | null => {
  const active = document.activeElement as HTMLElement | null

  if (!active) {
    return null
  }

  const li = active.closest<HTMLElement>('li')
  return li && el.value?.contains(li) ? li : null
}

const getRootMenu = (): HTMLElement | null =>
  el.value?.querySelector<HTMLElement>(':scope > ul, :scope > menu, :scope > nav') || null

const getActiveMenu = (): HTMLElement | null => {
  const focused = getFocusedItem()

  if (focused) {
    return (focused.closest('.submenu[data-open]') as HTMLElement) || (focused.parentElement as HTMLElement)
  }

  // Find the deepest open submenu, or fall back to the root menu
  const openSubmenus = el.value?.querySelectorAll<HTMLElement>('.submenu[data-open]')

  if (openSubmenus?.length) {
    return openSubmenus[openSubmenus.length - 1]
  }

  return getRootMenu()
}

const navigateVertical = (direction: 'up' | 'down') => {
  const menu = getActiveMenu()

  if (!menu) {
    return
  }

  const items = getMenuItems(menu)

  if (!items.length) {
    return
  }

  const focused = getFocusedItem()
  const currentIndex = focused ? items.indexOf(focused) : -1

  let nextIndex: number

  if (direction === 'down') {
    nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0
  } else {
    nextIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1
  }

  items[nextIndex]?.focus()
}

const openSubmenuOfFocused = async () => {
  const focused = getFocusedItem()

  if (!focused?.classList.contains('has-sub')) {
    return
  }

  const submenu = focused.querySelector<HTMLElement>('.submenu')

  if (!submenu) {
    return
  }

  await showSubmenu(focused as MenuItem, submenu)
  getMenuItems(submenu)[0]?.focus()
}

const closeSubmenuAndFocusParent = () => {
  const focused = getFocusedItem()
  const submenu = focused?.closest('.submenu[data-open]') as HTMLElement | null

  if (!submenu) {
    return
  }

  const parentItem = submenu.closest('li.has-sub') as MenuItem | null

  if (parentItem) {
    hideSubmenu(parentItem, submenu)
    parentItem.focus()
  }
}

const activateFocused = () => {
  const focused = getFocusedItem()

  if (!focused) {
    return
  }

  if (focused.classList.contains('has-sub')) {
    openSubmenuOfFocused()
  } else {
    focused.click()
  }
}

const onKeyDown = (event: KeyboardEvent) => {
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      navigateVertical('down')
      break

    case 'ArrowUp':
      event.preventDefault()
      navigateVertical('up')
      break

    case 'ArrowRight':
      event.preventDefault()
      openSubmenuOfFocused()
      break

    case 'ArrowLeft':
      event.preventDefault()
      closeSubmenuAndFocusParent()
      break

    case 'Enter':
      event.preventDefault()
      activateFocused()
      break

    case 'Escape':
      event.preventDefault()
      close()
      break
  }
}

const initSubmenus = () => {
  el.value?.querySelectorAll<HTMLElement>('.has-sub').forEach((item: MenuItem) => {
    const submenu = item.querySelector<HTMLElement>('.submenu')

    if (!submenu || item.eventsRegistered) {
      return
    }

    item.addEventListener('mouseenter', () => showSubmenu(item, submenu))

    item.addEventListener('mousemove', () => {
      if (submenu.hasAttribute('data-open')) {
        cancelHide(item)
      }
    })

    item.addEventListener('mouseleave', () => {
      scheduleHide(item, submenu)
    })

    submenu.addEventListener('mouseenter', () => {
      cancelHide(item)
    })

    submenu.addEventListener('mouseleave', () => {
      scheduleHide(item, submenu)
    })

    item.eventsRegistered = true
  })
}

let observer: MutationObserver | undefined

const startObservingSubmenus = () => {
  stopObservingSubmenus()

  if (!el.value) {
    return
  }

  observer = new MutationObserver(() => {
    initSubmenus()

    const active = document.activeElement as HTMLElement | null

    if (!active || !el.value?.contains(active)) {
      el.value?.focus()
    }
  })
  observer.observe(el.value, { childList: true, subtree: true })

  initSubmenus()
}

const stopObservingSubmenus = () => {
  observer?.disconnect()
  observer = undefined
}

const onPointerDownOutside = (event: PointerEvent) => {
  if (!el.value || !options.value.component) {
    return
  }
  const target = event.target as Node | null
  if (target && !el.value.contains(target)) {
    options.value = { component: null, position: { top: 0, left: 0 } }
  }
}

const open = async (top = 0, left = 0) => {
  if (!el.value) {
    return
  }

  // Position BEFORE showing so the menu never flashes at the user-agent default location.
  el.value.style.left = `${left}px`
  el.value.style.top = `${top}px`
  el.value.showPopover()
  el.value.focus()

  await nextTick()

  try {
    await positionAt(left, top)
  } catch (error: unknown) {
    logger.error(error)
  }

  // Defer attaching the outside-click listener so the gesture that opened the menu
  // (the right-click or button click) doesn't immediately dismiss it.
  setTimeout(() => document.addEventListener('pointerdown', onPointerDownOutside), 0)

  startObservingSubmenus()
}

const close = () => {
  stopObservingSubmenus()
  document.removeEventListener('pointerdown', onPointerDownOutside)
  el.value?.hidePopover()
  if (el.value) {
    el.value.style.left = '-9999px'
    el.value.style.top = '-9999px'
  }
}

onBeforeUnmount(() => {
  stopObservingSubmenus()
  document.removeEventListener('pointerdown', onPointerDownOutside)
  el.value?.querySelectorAll<HTMLElement>('.has-sub').forEach((item: MenuItem) => {
    clearTimeout(item.hideTimeout)
    item.hideTimeout = undefined
    item.eventsRegistered = false
  })
})

watch(options, newOptions => {
  if (newOptions.component) {
    open(newOptions.position.top, newOptions.position.left)
  } else {
    close()
  }
})
</script>

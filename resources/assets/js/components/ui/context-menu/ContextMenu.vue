<template>
  <dialog
    ref="el"
    v-koel-focus
    :class="extraClass"
    :style="{ top, left, bottom, right }"
    class="menu context-menu select-none shadow overflow-visible backdrop:opacity-0"
    tabindex="0"
    @mousedown="onMouseDown"
    @contextmenu.prevent
    @keydown.esc="close"
  >
    <component :is="options.component" v-if="options.component" v-bind="options.props" />
  </dialog>
</template>

<script lang="ts" setup>
import { nextTick, onBeforeUnmount, ref, toRefs, watch } from 'vue'
import { logger } from '@/utils/logger'
import { requireInjection } from '@/utils/helpers'
import { ContextMenuKey } from '@/config/symbols'

const props = defineProps<{ extraClass?: string }>()
const { extraClass } = toRefs(props)

const options = requireInjection(ContextMenuKey)

const el = ref<HTMLDialogElement>()
const top = ref('0')
const left = ref('0')
const bottom = ref('auto')
const right = ref('auto')

const preventOffScreen = async (element: HTMLElement, isSubmenu = false) => {
  const { bottom, right } = element.getBoundingClientRect()

  if (bottom > window.innerHeight) {
    element.style.top = 'auto'
    element.style.bottom = '0'
  } else {
    element.style.bottom = 'auto'
  }

  if (right > window.innerWidth) {
    element.style.right = isSubmenu ? `${el.value?.getBoundingClientRect().width}px` : '0'
    element.style.left = 'auto'
  } else {
    element.style.right = 'auto'
  }
}

type MenuItem = HTMLElement & {
  eventsRegistered?: boolean
  safeArea?: HTMLDivElement
}

const createSafeArea = (item: MenuItem): HTMLDivElement => {
  if (item.safeArea) {
    return item.safeArea
  }

  const div = document.createElement('div')
  div.style.cssText = 'position:fixed;z-index:1000;opacity:0;pointer-events:auto;display:none;'
  document.body.appendChild(div)
  item.safeArea = div

  div.addEventListener('mouseleave', () => {
    div.style.display = 'none'
  })

  return div
}

const updateSafeArea = (
  safeArea: HTMLDivElement,
  itemRect: DOMRect,
  submenuRect: DOMRect,
  cursorX: number,
  cursorY: number,
) => {
  // Build a bounding box that covers the gap between the cursor and the submenu.
  const boxLeft = Math.min(cursorX, submenuRect.left)
  const boxRight = Math.max(cursorX, submenuRect.right)
  const boxTop = Math.min(cursorY, submenuRect.top)
  const boxBottom = Math.max(cursorY, submenuRect.bottom)

  safeArea.style.left = `${boxLeft}px`
  safeArea.style.top = `${boxTop}px`
  safeArea.style.width = `${boxRight - boxLeft}px`
  safeArea.style.height = `${boxBottom - boxTop}px`
  safeArea.style.display = 'block'

  // Create a triangle clip-path from the cursor point to two corners of the submenu edge.
  const cx = cursorX - boxLeft
  const cy = cursorY - boxTop

  const submenuIsLeft = submenuRect.right <= itemRect.left

  let p1x: number, p1y: number, p2x: number, p2y: number

  if (submenuIsLeft) {
    // Submenu is to the left: triangle points to the submenu's right edge
    p1x = submenuRect.right - boxLeft
    p1y = submenuRect.top - boxTop
    p2x = submenuRect.right - boxLeft
    p2y = submenuRect.bottom - boxTop
  } else {
    // Submenu is to the right: triangle points to the submenu's left edge
    p1x = submenuRect.left - boxLeft
    p1y = submenuRect.top - boxTop
    p2x = submenuRect.left - boxLeft
    p2y = submenuRect.bottom - boxTop
  }

  safeArea.style.clipPath = `polygon(${cx}px ${cy}px, ${p1x}px ${p1y}px, ${p2x}px ${p2y}px)`
}

const cleanupSafeAreas = () => {
  el.value?.querySelectorAll<HTMLElement>('.has-sub').forEach((item: MenuItem) => {
    if (item.safeArea) {
      item.safeArea.remove()
      item.safeArea = undefined
    }
  })
}

const initSubmenus = () => {
  el.value?.querySelectorAll<HTMLElement>('.has-sub').forEach((item: MenuItem) => {
    const submenu = item.querySelector<HTMLElement>('.submenu')

    if (!submenu || item.eventsRegistered) {
      return
    }

    item.addEventListener('mouseenter', async () => {
      submenu.style.top = '0'
      submenu.style.left = '100%'
      submenu.style.bottom = 'auto'
      submenu.style.right = 'auto'
      submenu.style.display = 'block'

      await nextTick()
      await preventOffScreen(submenu, true)
    })

    item.addEventListener('mousemove', (e: MouseEvent) => {
      if (submenu.style.display !== 'block') {
        return
      }

      const safeArea = createSafeArea(item)
      const submenuRect = submenu.getBoundingClientRect()
      const itemRect = item.getBoundingClientRect()
      updateSafeArea(safeArea, itemRect, submenuRect, e.clientX, e.clientY)
    })

    item.addEventListener('mouseleave', (e: MouseEvent) => {
      const related = e.relatedTarget as Node | null

      // If the mouse moved into the submenu or the safe area, keep the submenu open.
      if (related && (submenu.contains(related) || item.safeArea?.contains(related))) {
        return
      }

      submenu.style.display = 'none'

      if (item.safeArea) {
        item.safeArea.style.display = 'none'
      }
    })

    submenu.addEventListener('mouseleave', (e: MouseEvent) => {
      const related = e.relatedTarget as Node | null

      // If the mouse moved back to the parent item or its safe area, keep the submenu open.
      if (related && (item.contains(related) || item.safeArea?.contains(related))) {
        return
      }

      submenu.style.display = 'none'

      if (item.safeArea) {
        item.safeArea.style.display = 'none'
      }
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

  observer = new MutationObserver(() => initSubmenus())
  observer.observe(el.value, { childList: true, subtree: true })

  initSubmenus()
}

const stopObservingSubmenus = () => {
  observer?.disconnect()
  observer = undefined
}

const open = async (t = 0, l = 0) => {
  top.value = `${t}px`
  left.value = `${l}px`
  bottom.value = 'auto'
  right.value = 'auto'
  el.value?.showModal()

  await nextTick()

  try {
    await preventOffScreen(el.value!)
  } catch (error: unknown) {
    logger.error(error)
  }

  startObservingSubmenus()
}

const close = () => {
  cleanupSafeAreas()
  stopObservingSubmenus()
  el.value?.close()
}

const onMouseDown = (e: MouseEvent) => e.target === el.value && close()

onBeforeUnmount(() => {
  cleanupSafeAreas()
  stopObservingSubmenus()
})

watch(options, newOptions => {
  if (newOptions.component) {
    open(newOptions.position.top, newOptions.position.left)
  } else {
    close()
  }
})
</script>

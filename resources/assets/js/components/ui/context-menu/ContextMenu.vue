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
  hideTimeout?: ReturnType<typeof setTimeout>
}

const HIDE_DELAY = 150

const hideSubmenu = (item: MenuItem, submenu: HTMLElement) => {
  submenu.style.display = 'none'
  submenu.style.top = '0'
  submenu.style.bottom = 'auto'
}

const scheduleHide = (item: MenuItem, submenu: HTMLElement) => {
  clearTimeout(item.hideTimeout)
  item.hideTimeout = setTimeout(() => hideSubmenu(item, submenu), HIDE_DELAY)
}

const cancelHide = (item: MenuItem) => {
  clearTimeout(item.hideTimeout)
  item.hideTimeout = undefined
}

const initSubmenus = () => {
  el.value?.querySelectorAll<HTMLElement>('.has-sub').forEach((item: MenuItem) => {
    const submenu = item.querySelector<HTMLElement>('.submenu')

    if (!submenu || item.eventsRegistered) {
      return
    }

    item.addEventListener('mouseenter', async () => {
      cancelHide(item)

      submenu.style.top = '0'
      submenu.style.left = '100%'
      submenu.style.bottom = 'auto'
      submenu.style.right = 'auto'
      submenu.style.display = 'block'

      await nextTick()
      await preventOffScreen(submenu, true)
    })

    item.addEventListener('mousemove', () => {
      if (submenu.style.display === 'block') {
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
  stopObservingSubmenus()
  el.value?.close()
}

const onMouseDown = (e: MouseEvent) => e.target === el.value && close()

onBeforeUnmount(() => {
  stopObservingSubmenus()
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

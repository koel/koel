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

const safeAreaHeight = ref('0px')
const safeAreaWidth = ref('0px')
const safeAreaClipPath = ref('polygon(0 0, 0 0, 0 0)')
const safeAreaLeft = ref('auto')
const safeAreaRight = ref('0')

type MenuItem = HTMLElement & {
  eventsRegistered?: boolean
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
      const submenuRect = submenu.getBoundingClientRect()
      const itemRect = item.getBoundingClientRect()
      const submenuIsLeft = submenuRect.right <= itemRect.left

      safeAreaHeight.value = `${submenuRect.height}px`

      if (submenuIsLeft) {
        const gap = e.clientX - submenuRect.right
        safeAreaWidth.value = `${gap}px`
        safeAreaLeft.value = '0'
        safeAreaRight.value = 'auto'
        safeAreaClipPath.value = `polygon(0 0, 100% ${e.clientY - submenuRect.top}px, 0 100%)`
      } else {
        const gap = submenuRect.x - e.clientX
        safeAreaWidth.value = `${gap}px`
        safeAreaLeft.value = 'auto'
        safeAreaRight.value = '0'
        safeAreaClipPath.value = `polygon(100% 0, 0 ${e.clientY - submenuRect.top}px, 100% 100%)`
      }
    })

    item.addEventListener('mouseleave', () => {
      submenu.style.top = '0'
      submenu.style.bottom = 'auto'
      submenu.style.display = 'none'
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

  // Also init immediately in case the content is already rendered
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

// Close the context menu when clicking outside it.
const onMouseDown = (e: MouseEvent) => e.target === el.value && close()

onBeforeUnmount(stopObservingSubmenus)

watch(options, newOptions => {
  if (newOptions.component) {
    open(newOptions.position.top, newOptions.position.left)
  } else {
    close()
  }
})
</script>

<style lang="postcss" scoped>
dialog {
  :deep(.has-sub) {
    @apply after:absolute after:top-0 after:z-[2] after:opacity-0 after:content-[''];
  }

  :deep(.has-sub)::after {
    left: v-bind(safeAreaLeft);
    right: v-bind(safeAreaRight);
    width: v-bind(safeAreaWidth);
    height: v-bind(safeAreaHeight);
    clip-path: v-bind(safeAreaClipPath);
  }
}
</style>

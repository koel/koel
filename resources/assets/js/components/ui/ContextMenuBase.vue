<template>
  <nav
    v-if="shown"
    ref="el"
    v-koel-clickaway="close"
    v-koel-focus
    :class="extraClass"
    :style="{ top: `${top}px`, left: `${left}px` }"
    class="menu context-menu"
    tabindex="0"
    @contextmenu.prevent
    @keydown.esc="close"
  >
    <ul>
      <slot>Menu items go here.</slot>
    </ul>
  </nav>
</template>

<script lang="ts" setup>
import { nextTick, ref, toRefs } from 'vue'
import { eventBus } from '@/utils'

const props = defineProps<{ extraClass?: string }>()
const { extraClass } = toRefs(props)

const el = ref<HTMLElement>()
const shown = ref(false)
const top = ref(0)
const left = ref(0)

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

const initSubmenus = () => {
  el.value?.querySelectorAll('.has-sub').forEach(item => {
    const submenu = item.querySelector<HTMLElement>('.submenu')

    if (!submenu) {
      return
    }

    item.addEventListener('mouseenter', async () => {
      submenu.style.display = 'block'
      await nextTick()
      await preventOffScreen(submenu, true)
    })

    item.addEventListener('mouseleave', () => {
      submenu.style.top = '0'
      submenu.style.bottom = 'auto'
      submenu.style.display = 'none'
    })
  })
}

const open = async (_top = 0, _left = 0) => {
  top.value = _top
  left.value = _left
  shown.value = true

  await nextTick()

  try {
    await preventOffScreen(el.value!)
    await initSubmenus()
  } catch (e) {
    console.error(e)
    // in a non-browser environment (e.g., unit testing), these two functions are broken due to calls to
    // getBoundingClientRect() and querySelectorAll()
  }

  eventBus.emit('CONTEXT_MENU_OPENED', el)
}

const close = () => {
  shown.value = false
}

// ensure there's only one context menu at any time
eventBus.on('CONTEXT_MENU_OPENED', target => target === el || close())

defineExpose({ open, close, shown })
</script>

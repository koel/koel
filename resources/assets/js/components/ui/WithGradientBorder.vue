<template>
  <div ref="el" class="gradient-border relative" v-bind="$attrs">
    <div class="border-base absolute inset-0 rounded-[inherit]" />
    <div class="gradient-overlay absolute motion-reduce:hidden inset-0 rounded-[inherit]" />
    <div class="relative h-full rounded-[inherit] overflow-hidden">
      <slot />
    </div>
  </div>
</template>

<script lang="ts">
import type { Fn } from '@vueuse/core'

// Shared scroll listener registry: one listener per scrollable container, many subscribers.
const scrollSubscribers = new Map<Element, Set<Fn>>()

const findScrollParent = (el: HTMLElement): Element | null => {
  let parent = el.parentElement

  while (parent) {
    const { overflow, overflowY } = getComputedStyle(parent)

    if (/auto|scroll/.test(overflow + overflowY)) {
      return parent
    }

    parent = parent.parentElement
  }

  return null
}

const scrollHandlers = new Map<Element, EventListener>()

const subscribeToScroll = (container: Element, cb: Fn): Fn => {
  let subs = scrollSubscribers.get(container)

  if (!subs) {
    subs = new Set()
    scrollSubscribers.set(container, subs)

    const onScroll = () => {
      for (const fn of scrollSubscribers.get(container) || []) {
        fn()
      }
    }

    scrollHandlers.set(container, onScroll)
    container.addEventListener('scroll', onScroll, { passive: true })
  }

  subs.add(cb)

  return () => {
    const current = scrollSubscribers.get(container)
    current?.delete(cb)

    if (!current || current.size === 0) {
      const handler = scrollHandlers.get(container)

      if (handler) {
        container.removeEventListener('scroll', handler)
        scrollHandlers.delete(container)
      }

      scrollSubscribers.delete(container)
    }
  }
}
</script>

<script lang="ts" setup>
import { usePointer, useThrottleFn } from '@vueuse/core'
import { onBeforeUnmount, onMounted, reactive, ref, toRef, type WatchHandle } from 'vue'
import { watch } from 'vue'

const props = withDefaults(
  defineProps<{
    color?: string
    borderColor?: string
    borderWidth?: string
  }>(),
  {
    color: 'rgb(236 72 153)',
    borderColor: 'transparent',
    borderWidth: '1px',
  },
)

defineOptions({
  inheritAttrs: false,
})

const color = toRef(props, 'color')
const borderColor = toRef(props, 'borderColor')
const el = ref<HTMLElement | null>(null)

const pointer = reactive(usePointer())
const x = ref('0')
const y = ref('0')
const size = ref('0')

const update = useThrottleFn(() => {
  const rect = el.value?.getBoundingClientRect()
  if (!rect) return

  x.value = `${pointer.x - rect.left}px`
  y.value = `${pointer.y - rect.top}px`
  size.value = `${(Math.max(rect.width, rect.height) * 2) / 3}px`
}, 50)

let pointerWatch: WatchHandle | undefined
let unsubscribeScroll: Fn | undefined
let scrollParent: Element | null = null
let observer: IntersectionObserver | undefined

const activate = () => {
  if (pointerWatch) return

  pointerWatch = watch(pointer, update, { deep: true, immediate: true })

  if (scrollParent) {
    unsubscribeScroll = subscribeToScroll(scrollParent, update)
  }
}

const deactivate = () => {
  pointerWatch?.()
  pointerWatch = undefined
  unsubscribeScroll?.()
  unsubscribeScroll = undefined
}

onMounted(() => {
  if (!el.value) return

  scrollParent = findScrollParent(el.value)

  observer = new IntersectionObserver(
    entries => {
      if (entries[0].isIntersecting) {
        activate()
      } else {
        deactivate()
      }
    },
    { rootMargin: '100px' },
  )

  observer.observe(el.value)
})

onBeforeUnmount(() => {
  deactivate()
  observer?.disconnect()
})
</script>

<style scoped>
.gradient-border {
  --gradient-border-width: v-bind(borderWidth);
}

/* Static base border — always visible */
.border-base {
  background: v-bind(borderColor);
  mask:
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  padding: var(--gradient-border-width);
}

/* Animated gradient border — overlays the base, fading from highlight to the base border color */
.gradient-overlay {
  background: radial-gradient(v-bind(size) circle at v-bind(x) v-bind(y), v-bind(color), v-bind(borderColor) 100%);
  will-change: background;
  mask:
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  padding: var(--gradient-border-width);
}
</style>

<template>
  <div class="w-full min-w-0">
    <Teleport v-if="actionsHost && hasOverflow" :to="actionsHost">
      <button
        type="button"
        class="w-9 h-9 rounded-full flex items-center justify-center text-k-fg-70 hover:text-k-fg hover:bg-k-fg-5 transition"
        title="Scroll left"
        @click="slide(-1)"
      >
        <Icon :icon="faChevronLeft" />
        <span class="sr-only">Scroll left</span>
      </button>
      <button
        type="button"
        class="w-9 h-9 rounded-full flex items-center justify-center text-k-fg-70 hover:text-k-fg hover:bg-k-fg-5 transition"
        title="Scroll right"
        @click="slide(1)"
      >
        <Icon :icon="faChevronRight" />
        <span class="sr-only">Scroll right</span>
      </button>
    </Teleport>

    <nav v-else-if="hasOverflow" class="flex justify-end gap-2 mb-2">
      <button
        type="button"
        class="w-9 h-9 rounded-full flex items-center justify-center text-k-fg-70 hover:text-k-fg hover:bg-k-fg-5 transition"
        title="Scroll left"
        @click="slide(-1)"
      >
        <Icon :icon="faChevronLeft" />
        <span class="sr-only">Scroll left</span>
      </button>
      <button
        type="button"
        class="w-9 h-9 rounded-full flex items-center justify-center text-k-fg-70 hover:text-k-fg hover:bg-k-fg-5 transition"
        title="Scroll right"
        @click="slide(1)"
      >
        <Icon :icon="faChevronRight" />
        <span class="sr-only">Scroll right</span>
      </button>
    </nav>

    <div
      ref="scroller"
      class="home-carousel scroll-mask-x-from-[calc(100%-2rem)] md:scroll-mask-x overflow-x-auto overflow-y-hidden w-full"
    >
      <div class="home-carousel-track flex gap-4">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { faChevronLeft, faChevronRight } from '@fortawesome/free-solid-svg-icons'
import { inject, onBeforeUnmount, onMounted, onUpdated, ref, watch } from 'vue'
import { BlockActionsHostKey } from '@/config/symbols'

const actionsHost = inject(BlockActionsHostKey, ref(null))

const scroller = ref<HTMLDivElement>()
const hasOverflow = ref(false)

let resizeObserver: ResizeObserver | undefined

const updateOverflow = () => {
  const el = scroller.value

  if (!el) {
    hasOverflow.value = false
    return
  }

  hasOverflow.value = el.scrollWidth > el.clientWidth + 1
}

const observeOverflow = (el: HTMLDivElement | undefined) => {
  resizeObserver?.disconnect()
  resizeObserver = undefined

  if (!el) {
    return
  }

  resizeObserver = new ResizeObserver(updateOverflow)
  resizeObserver.observe(el)
  resizeObserver.observe(el.firstElementChild ?? el)
  updateOverflow()
}

onMounted(() => {
  observeOverflow(scroller.value)
  window.addEventListener('resize', updateOverflow)
})

onUpdated(updateOverflow)

onBeforeUnmount(() => {
  resizeObserver?.disconnect()
  window.removeEventListener('resize', updateOverflow)
})

watch(scroller, observeOverflow)

const slide = (direction: 1 | -1) => {
  const el = scroller.value
  if (!el) {
    return
  }
  const max = el.scrollWidth - el.clientWidth
  const target = Math.max(0, Math.min(max, el.scrollLeft + direction * el.clientWidth))
  el.scrollTo({ left: target, behavior: 'smooth' })
}
</script>

<style lang="postcss">
.home-carousel {
  scrollbar-width: none;
}

.home-carousel::-webkit-scrollbar {
  display: none;
}

.home-carousel-track > * {
  flex: none;
  width: 240px;
}
</style>

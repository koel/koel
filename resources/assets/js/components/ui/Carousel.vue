<template>
  <section class="w-full min-w-0">
    <header class="flex items-center justify-between mb-4">
      <h3 class="text-2xl font-thin text-k-fg flex items-center gap-3">
        <slot name="header" />
      </h3>
      <nav class="flex gap-2">
        <button
          v-if="onRefresh"
          type="button"
          class="w-9 h-9 rounded-full flex items-center justify-center text-k-fg-70 hover:text-k-fg hover:bg-k-fg-5 transition disabled:opacity-50 disabled:cursor-not-allowed"
          :class="{ 'animate-spin': refreshing }"
          :disabled="refreshing"
          title="Refresh"
          @click="refresh"
        >
          <Icon :icon="faRotateRight" />
          <span class="sr-only">Refresh</span>
        </button>
        <template v-if="hasOverflow">
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
        </template>
      </nav>
    </header>

    <div
      ref="scroller"
      class="home-carousel scroll-mask-x-from-[calc(100%-2rem)] md:scroll-mask-x overflow-x-auto overflow-y-hidden w-full"
    >
      <div class="home-carousel-track flex gap-4">
        <slot />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { faChevronLeft, faChevronRight, faRotateRight } from '@fortawesome/free-solid-svg-icons'
import { onBeforeUnmount, onMounted, onUpdated, ref, watch } from 'vue'

const props = defineProps<{ onRefresh?: () => Promise<unknown> | unknown }>()

const scroller = ref<HTMLDivElement>()
const refreshing = ref(false)
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

const refresh = async () => {
  if (!props.onRefresh || refreshing.value) {
    return
  }

  refreshing.value = true

  try {
    await props.onRefresh()
  } finally {
    refreshing.value = false
  }
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

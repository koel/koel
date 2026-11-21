<template>
  <div class="carousel relative w-full min-w-0">
    <div
      ref="scroller"
      class="home-carousel scroll-mask-x-from-[calc(100%-2rem)] md:scroll-mask-x overflow-x-auto overflow-y-hidden w-full"
      @scroll.passive="updateScrollState"
    >
      <div class="home-carousel-track flex gap-6">
        <slot />
      </div>
    </div>

    <button v-if="canScrollLeft" class="scroll-button left-2" type="button" @click="slide(-1)">
      <Icon :icon="faChevronLeft" />
      <span class="sr-only">Scroll left</span>
    </button>

    <button v-if="canScrollRight" class="scroll-button right-2" type="button" @click="slide(1)">
      <Icon :icon="faChevronRight" />
      <span class="sr-only">Scroll right</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { faChevronLeft, faChevronRight } from '@fortawesome/free-solid-svg-icons'
import { onBeforeUnmount, onMounted, onUpdated, ref, watch } from 'vue'

const scroller = ref<HTMLDivElement>()
const canScrollLeft = ref(false)
const canScrollRight = ref(false)

let resizeObserver: ResizeObserver | undefined

const updateScrollState = () => {
  const el = scroller.value

  if (!el) {
    canScrollLeft.value = false
    canScrollRight.value = false
    return
  }

  canScrollLeft.value = el.scrollLeft > 1
  canScrollRight.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 1
}

const observeScrollState = (el: HTMLDivElement | undefined) => {
  resizeObserver?.disconnect()
  resizeObserver = undefined

  if (!el) {
    return
  }

  resizeObserver = new ResizeObserver(updateScrollState)
  resizeObserver.observe(el)
  resizeObserver.observe(el.firstElementChild ?? el)
  updateScrollState()
}

onMounted(() => {
  observeScrollState(scroller.value)
  window.addEventListener('resize', updateScrollState)
})

onUpdated(updateScrollState)

onBeforeUnmount(() => {
  resizeObserver?.disconnect()
  window.removeEventListener('resize', updateScrollState)
})

watch(scroller, observeScrollState)

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

<style lang="postcss" scoped>
@reference '@css/app.pcss';
.scroll-button {
  @apply absolute top-1/2 -translate-y-1/2 z-10 w-14 h-14 text-xl rounded-full flex items-center justify-center
    bg-k-fg-10 border border-k-fg-10 text-k-fg-80 backdrop-blur-xl backdrop-saturate-150 shadow-lg opacity-0 transition hover:text-k-fg hover:bg-k-fg-20 active:scale-90
    focus-visible:opacity-100 no-hover:opacity-100;

  .carousel:hover & {
    @apply opacity-100;
  }
}
</style>

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

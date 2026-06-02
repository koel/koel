<template>
  <section class="w-full min-w-0">
    <header class="flex items-center justify-between mb-4">
      <h3 class="text-2xl font-thin text-k-fg flex items-center gap-3">
        <slot name="header" />
      </h3>
      <nav class="flex gap-2">
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
    </header>

    <div ref="scroller" class="home-carousel scroll-mask-x overflow-x-auto overflow-y-hidden w-full">
      <div class="home-carousel-track flex gap-4">
        <slot />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { faChevronLeft, faChevronRight } from '@fortawesome/free-solid-svg-icons'
import { ref } from 'vue'

const scroller = ref<HTMLDivElement>()

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

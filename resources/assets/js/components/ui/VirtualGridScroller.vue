<template>
  <div
    ref="scroller"
    v-koel-overflow-fade
    class="virtual-grid-scroller will-change-transform overflow-scroll"
    @scroll.passive="onScroll"
  >
    <div :style="{ height: `${totalHeight}px` }" class="will-change-transform overflow-hidden">
      <div
        :style="{
          transform: `translateY(${offsetY}px)`,
          display: 'grid',
          gridTemplateColumns: `repeat(${columnCount}, minmax(0, 1fr))`,
          gap: `${gap}px`,
          padding: `${padding}px`,
        }"
        class="will-change-transform"
      >
        <slot v-for="item in renderedItems" :item="item" />
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { computed, onBeforeUnmount, onMounted, ref, toRefs, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    items: any[]
    itemHeight: number
    minItemWidth: number
    gap?: number
    padding?: number
  }>(),
  {
    gap: 20,
    padding: 24,
  },
)

const emit = defineEmits<{
  (e: 'scrolled-to-end'): void
  (e: 'scroll', event: Event): void
}>()

const { items, itemHeight, minItemWidth, gap, padding } = toRefs(props)

const scroller = ref<HTMLElement>()
const scrollerWidth = ref(0)
const scrollerHeight = ref(0)
const scrollTop = ref(0)
const renderAhead = 3

const columnCount = computed(() => {
  const available = scrollerWidth.value - padding.value * 2
  return Math.max(1, Math.floor((available + gap.value) / (minItemWidth.value + gap.value)))
})

const rowCount = computed(() => Math.ceil(items.value.length / columnCount.value))
const rowHeight = computed(() => itemHeight.value + gap.value)
const totalHeight = computed(() => rowCount.value * rowHeight.value + padding.value * 2)

const startRow = computed(() => Math.max(0, Math.floor(scrollTop.value / rowHeight.value) - renderAhead))
const offsetY = computed(() => startRow.value * rowHeight.value)

const renderedItems = computed(() => {
  const visibleRows = Math.ceil(scrollerHeight.value / rowHeight.value) + 2 * renderAhead
  const endRow = Math.min(rowCount.value, startRow.value + visibleRows)
  const startIndex = startRow.value * columnCount.value
  const endIndex = Math.min(items.value.length, endRow * columnCount.value)
  return items.value.slice(startIndex, endIndex)
})

const onScroll = (e: Event) =>
  requestAnimationFrame(() => {
    scrollTop.value = (e.target as HTMLElement).scrollTop

    if (!scroller.value) {
      return
    }

    emit('scroll', e)

    if (scroller.value.scrollTop + scroller.value.clientHeight + rowHeight.value >= scroller.value.scrollHeight) {
      emit('scrolled-to-end')
    }
  })

const observer = new ResizeObserver((entries: ResizeObserverEntry[]) => {
  for (const entry of entries) {
    scrollerWidth.value = entry.contentRect.width
    scrollerHeight.value = entry.contentRect.height
  }
})

onMounted(() => {
  if (scroller.value) {
    observer.observe(scroller.value)
    scrollerWidth.value = scroller.value.offsetWidth
    scrollerHeight.value = scroller.value.offsetHeight
  }
})

onBeforeUnmount(() => {
  if (scroller.value) {
    observer.unobserve(scroller.value)
  }
})

const scrollToTop = () => {
  scroller.value?.scrollTo({ top: 0, behavior: 'smooth' })
}

// Reset scroll when items change drastically (e.g. sort/filter)
watch(
  () => items.value.length,
  (newLen, oldLen) => {
    if (newLen < oldLen) {
      scrollTop.value = 0
    }
  },
)

defineExpose({ scrollToTop, columnCount })
</script>

<style lang="postcss" scoped>
.virtual-grid-scroller {
  @supports (scrollbar-gutter: stable) {
    overflow: auto;
    scrollbar-gutter: stable;

    @media (hover: none) {
      scrollbar-gutter: auto;
    }
  }
}
</style>

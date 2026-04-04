<template>
  <div
    ref="scroller"
    v-koel-overflow-fade
    class="virtual-grid-scroller will-change-transform overflow-scroll"
    @scroll.passive="onScroll"
  >
    <!-- Measuring phase: render one item in a real grid to measure height and gap -->
    <div v-if="measuring" ref="measureContainer" class="measure-grid">
      <slot :item="items[0]" />
    </div>

    <!-- Virtual scrolling phase -->
    <template v-else>
      <div :style="{ height: `${totalHeight}px` }" class="will-change-transform overflow-hidden">
        <div :style="gridStyle" class="will-change-transform">
          <slot v-for="item in renderedItems" :item="item" />
        </div>
      </div>
    </template>
  </div>
</template>

<script lang="ts" setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, toRefs, watch } from 'vue'

const props = defineProps<{
  items: any[]
  minItemWidth: number
}>()

const emit = defineEmits<{
  (e: 'scrolled-to-end'): void
  (e: 'scroll', event: Event): void
}>()

const { items, minItemWidth } = toRefs(props)

const scroller = ref<HTMLElement>()
const measureContainer = ref<HTMLElement>()
const scrollerWidth = ref(0)
const scrollerHeight = ref(0)
const scrollTop = ref(0)
const measuredItemHeight = ref(0)
const measuredGap = ref(0)
const measuring = ref(true)
const renderAhead = 3

const columnCount = computed(() => {
  const available = scrollerWidth.value
  const g = measuredGap.value || 0
  return Math.max(1, Math.floor((available + g) / (minItemWidth.value + g)))
})

const rowCount = computed(() => Math.ceil(items.value.length / columnCount.value))
const rowHeight = computed(() => measuredItemHeight.value + measuredGap.value)
const totalHeight = computed(() => (rowCount.value ? rowCount.value * rowHeight.value - measuredGap.value : 0))

const startRow = computed(() => Math.max(0, Math.floor(scrollTop.value / rowHeight.value) - renderAhead))
const offsetY = computed(() => startRow.value * rowHeight.value)

const renderedItems = computed(() => {
  if (measuring.value || !measuredItemHeight.value) {
    return []
  }

  const visibleRows = Math.ceil(scrollerHeight.value / rowHeight.value) + 2 * renderAhead
  const endRow = Math.min(rowCount.value, startRow.value + visibleRows)
  const startIndex = startRow.value * columnCount.value
  const endIndex = Math.min(items.value.length, endRow * columnCount.value)
  return items.value.slice(startIndex, endIndex)
})

const gridStyle = computed(() => ({
  transform: `translateY(${offsetY.value}px)`,
  display: 'grid',
  gridTemplateColumns: `repeat(${columnCount.value}, minmax(0, 1fr))`,
  gap: `${measuredGap.value}px`,
}))

const measureItem = async () => {
  if (!items.value.length) {
    return
  }

  measuring.value = true
  await nextTick()

  if (measureContainer.value) {
    const style = getComputedStyle(measureContainer.value)
    measuredGap.value = parseFloat(style.rowGap) || parseFloat(style.gap) || 0

    const firstChild = measureContainer.value.firstElementChild as HTMLElement | null

    if (firstChild) {
      measuredItemHeight.value = firstChild.offsetHeight
    }
  }

  measuring.value = false
}

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

const resizeObserver = new ResizeObserver((entries: ResizeObserverEntry[]) => {
  for (const entry of entries) {
    scrollerWidth.value = entry.contentRect.width
    scrollerHeight.value = entry.contentRect.height
  }
})

onMounted(async () => {
  if (scroller.value) {
    resizeObserver.observe(scroller.value)
    scrollerWidth.value = scroller.value.offsetWidth
    scrollerHeight.value = scroller.value.offsetHeight
  }

  await measureItem()
})

onBeforeUnmount(() => {
  if (scroller.value) {
    resizeObserver.unobserve(scroller.value)
  }
})

const scrollToTop = () => {
  scroller.value?.scrollTo({ top: 0, behavior: 'smooth' })
}

// Re-measure when minItemWidth changes (view mode switch)
watch(minItemWidth, () => measureItem())

// Reset scroll when items shrink (sort/filter reset)
watch(
  () => items.value.length,
  (newLen: number, oldLen: number) => {
    if (newLen < oldLen) {
      scrollTop.value = 0
    }

    if (oldLen === 0 && newLen > 0 && !measuredItemHeight.value) {
      measureItem()
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

.measure-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(v-bind('`${minItemWidth}px`'), 1fr));
}
</style>

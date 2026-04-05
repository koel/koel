<template>
  <div
    ref="scroller"
    v-koel-overflow-fade
    class="virtual-grid-scroller will-change-transform overflow-scroll h-full"
    @scroll.passive="onScroll"
  >
    <!-- Measuring phase: render one item to measure height, gap, and padding -->
    <div v-if="measuring && items.length" ref="measureContainer" v-bind="$attrs" class="grid">
      <slot :item="items[0]" />
    </div>

    <template v-else>
      <div class="height-container will-change-transform overflow-hidden">
        <div v-bind="$attrs" class="grid-container will-change-transform grid">
          <slot v-for="item in renderedItems" :item />
        </div>
      </div>
    </template>
  </div>
</template>

<script lang="ts" setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, toRefs, watch } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps<{
  items: any[]
  minItemWidth: number
}>()

const emit = defineEmits<{ (e: 'scrolled-to-end'): void }>()
const { items, minItemWidth } = toRefs(props)

const scroller = ref<HTMLElement>()
const measureContainer = ref<HTMLElement>()
const scrollerWidth = ref(0)
const scrollerHeight = ref(0)
const scrollTop = ref(0)
const measuredItemHeight = ref(0)
const measuredRowGap = ref(0)
const measuredColumnGap = ref(0)
const measuredPaddingX = ref(0)
const measuredPaddingY = ref(0)
const measuring = ref(true)

const renderAhead = 3

const columnCount = computed(() => {
  const contentWidth = scrollerWidth.value - measuredPaddingX.value
  const g = measuredColumnGap.value
  return Math.max(1, Math.floor((contentWidth + g) / (minItemWidth.value + g)))
})

const rowCount = computed(() => Math.ceil(items.value.length / columnCount.value))
const rowHeight = computed(() => measuredItemHeight.value + measuredRowGap.value)

const totalHeight = computed(() =>
  rowCount.value ? rowCount.value * rowHeight.value - measuredRowGap.value + measuredPaddingY.value : 0,
)

const startRow = computed(() => Math.max(0, Math.floor(scrollTop.value / rowHeight.value) - renderAhead))
const offsetY = computed(() => startRow.value * rowHeight.value)

const renderedItems = computed(() => {
  if (measuring.value || !measuredItemHeight.value) {
    return []
  }

  const visibleRows = Math.ceil(scrollerHeight.value / rowHeight.value) + 2 * renderAhead
  const endRow = Math.min(rowCount.value, startRow.value + visibleRows)
  return items.value.slice(startRow.value * columnCount.value, endRow * columnCount.value)
})

const cssHeight = computed(() => `${totalHeight.value}px`)
const cssTransform = computed(() => `translateY(${offsetY.value}px)`)
const cssColumns = computed(() => `repeat(${columnCount.value}, minmax(0, 1fr))`)

const measure = async () => {
  if (!items.value.length) {
    measuring.value = false
    return
  }

  measuring.value = true
  await nextTick()

  if (!measureContainer.value) {
    measuring.value = false
    return
  }

  const style = getComputedStyle(measureContainer.value)
  measuredRowGap.value = parseFloat(style.rowGap) || parseFloat(style.gap) || 0
  measuredColumnGap.value = parseFloat(style.columnGap) || parseFloat(style.gap) || 0
  measuredPaddingX.value = (parseFloat(style.paddingLeft) || 0) + (parseFloat(style.paddingRight) || 0)
  measuredPaddingY.value = (parseFloat(style.paddingTop) || 0) + (parseFloat(style.paddingBottom) || 0)

  const firstChild = measureContainer.value.firstElementChild as HTMLElement | null

  if (firstChild) {
    measuredItemHeight.value = firstChild.offsetHeight
  }

  measuring.value = false
}

let scrollRafId = 0

const onScroll = (e: Event) => {
  cancelAnimationFrame(scrollRafId)

  scrollRafId = requestAnimationFrame(() => {
    const el = scroller.value

    if (!el) {
      return
    }

    scrollTop.value = (e.target as HTMLElement).scrollTop

    if (el.scrollTop + el.clientHeight + rowHeight.value >= el.scrollHeight) {
      emit('scrolled-to-end')
    }
  })
}

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

  await measure()
})

onBeforeUnmount(() => {
  cancelAnimationFrame(scrollRafId)

  if (scroller.value) {
    resizeObserver.unobserve(scroller.value)
  }
})

const scrollToTop = () => scroller.value?.scrollTo({ top: 0, behavior: 'smooth' })

watch(minItemWidth, () => measure())

watch(
  () => items.value.length,
  async (newLen: number, oldLen: number) => {
    if (newLen < oldLen) {
      scrollTop.value = 0
    }

    if (oldLen === 0 && newLen > 0 && !measuredItemHeight.value) {
      await measure()
    }
  },
)

defineExpose({ scrollToTop })
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

.height-container {
  height: v-bind(cssHeight);
}

.grid-container {
  transform: v-bind(cssTransform);
  grid-template-columns: v-bind(cssColumns);
}
</style>

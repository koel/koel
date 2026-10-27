<template>
  <div
    ref="scroller"
    :class="{
      'scroll-mask-b scroll-mask-t-from-100%': carriesScreenHeader && !$slots.before,
      'scroll-mask-y': !carriesScreenHeader && !$slots.before,
    }"
    class="virtual-scroller will-change-transform overflow-scroll"
    @scroll.passive="onScroll"
  >
    <ScreenHeaderSpace v-if="carriesScreenHeader" />
    <slot name="before" />
    <div ref="itemsBox" :style="{ height: `${totalHeight}px` }" class="will-change-transform overflow-hidden">
      <div :style="{ transform: `translateY(${offsetY}px)` }" class="will-change-transform items-wrapper">
        <slot v-for="item in renderedItems" :item="item" />
        <slot v-for="i in renderedPlaceholderCount" name="placeholder" />
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { computed, onBeforeUnmount, onMounted, ref, toRefs } from 'vue'
import { useScreenHeaderScroller } from '@/composables/useScreenHeaderScroller'
import { isNearScrollEnd } from '@/utils/infiniteScroll'

import ScreenHeaderSpace from '@/components/ui/ScreenHeaderSpace.vue'

const props = withDefaults(
  defineProps<{
    items: any[]
    itemHeight: number
    /** Scrolls the screen header away with the items and leaves room for it at the top. */
    carriesScreenHeader?: boolean
    loadingMore?: boolean
  }>(),
  { carriesScreenHeader: false, loadingMore: false },
)
const emit = defineEmits<{
  (e: 'scrolled-to-end'): void
  (e: 'scroll', event: Event): void
}>()

const { items, itemHeight, loadingMore } = toRefs(props)

const scroller = ref<HTMLElement>()

useScreenHeaderScroller(() => (props.carriesScreenHeader ? scroller.value : null))
const itemsBox = ref<HTMLElement>()
const itemsTop = ref(0)
const scrollerHeight = ref(0)
const renderAhead = 5
const placeholderRowCount = 3
const scrollTop = ref(0)

const placeholderCount = computed(() => (loadingMore.value && items.value.length ? placeholderRowCount : 0))
const totalHeight = computed(() => (items.value.length + placeholderCount.value) * itemHeight.value)
const startPosition = computed(() =>
  Math.max(0, Math.floor((scrollTop.value - itemsTop.value) / itemHeight.value) - renderAhead),
)
const offsetY = computed(() => startPosition.value * itemHeight.value)

const renderedEnd = computed(() => {
  const count = Math.ceil(scrollerHeight.value / itemHeight.value) + 2 * renderAhead

  return Math.min(items.value.length + placeholderCount.value, startPosition.value + count)
})

const renderedItems = computed(() => items.value.slice(startPosition.value, renderedEnd.value))

const renderedPlaceholderCount = computed(() =>
  Math.max(0, renderedEnd.value - Math.max(startPosition.value, items.value.length)),
)

const onScroll = (e: Event) =>
  requestAnimationFrame(() => {
    scrollTop.value = (e.target as HTMLElement).scrollTop
    itemsTop.value = itemsBox.value?.offsetTop ?? 0

    if (!scroller.value) {
      return
    }

    emit('scroll', e)

    if (isNearScrollEnd(scroller.value)) {
      emit('scrolled-to-end')
    }
  })

const observer = new ResizeObserver(entries => entries.forEach(el => (scrollerHeight.value = el.contentRect.height)))

onMounted(() => {
  observer.observe(scroller.value!)
  scrollerHeight.value = scroller.value!.offsetHeight
  itemsTop.value = itemsBox.value?.offsetTop ?? 0
})

onBeforeUnmount(() => observer.unobserve(scroller.value!))

const scrollToIndex = (index: number) => {
  if (!scroller.value) {
    return
  }

  itemsTop.value = itemsBox.value?.offsetTop ?? 0

  const top = itemsTop.value + index * itemHeight.value - scrollerHeight.value / 2 + itemHeight.value / 2
  scroller.value.scrollTo({ top: Math.max(0, top), behavior: 'smooth' })
}

const scrollToIndexIfHidden = (index: number) => {
  if (!scroller.value) {
    return
  }

  itemsTop.value = itemsBox.value?.offsetTop ?? 0

  const itemTop = itemsTop.value + index * itemHeight.value
  const visibleTop = scroller.value.scrollTop + itemHeight.value
  const visibleBottom = scroller.value.scrollTop + scroller.value.clientHeight - itemHeight.value

  if (itemTop < visibleTop || itemTop + itemHeight.value > visibleBottom) {
    scrollToIndex(index)
  }
}

defineExpose({ scrollToIndex, scrollToIndexIfHidden, getScrollerElement: () => scroller.value })
</script>

<style lang="postcss" scoped>
.virtual-scroller {
  @supports (scrollbar-gutter: stable) {
    overflow: auto;
    scrollbar-gutter: stable;

    @media (hover: none) {
      scrollbar-gutter: auto;
    }
  }
}
</style>

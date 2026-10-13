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
      <div
        v-bind="listAttrs"
        :style="{ transform: `translateY(${offsetY}px)` }"
        class="will-change-transform items-wrapper"
      >
        <slot v-for="item in renderedItems" :item="item" />
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { computed, onBeforeUnmount, onMounted, ref, toRefs } from 'vue'
import { useScreenHeaderScroller } from '@/composables/useScreenHeaderScroller'

import ScreenHeaderSpace from '@/components/ui/ScreenHeaderSpace.vue'

const props = withDefaults(
  defineProps<{
    items: any[]
    itemHeight: number
    /** Scrolls the screen header away with the items and leaves room for it at the top. */
    carriesScreenHeader?: boolean
    listAttrs?: Record<string, string>
  }>(),
  { carriesScreenHeader: false, listAttrs: () => ({}) },
)
const emit = defineEmits<{
  (e: 'scrolled-to-end'): void
  (e: 'scroll', event: Event): void
}>()

const { items, itemHeight } = toRefs(props)

const scroller = ref<HTMLElement>()

useScreenHeaderScroller(() => (props.carriesScreenHeader ? scroller.value : null))
const itemsBox = ref<HTMLElement>()
const itemsTop = ref(0)
const scrollerHeight = ref(0)
const renderAhead = 5
const scrollTop = ref(0)

const totalHeight = computed(() => items.value.length * itemHeight.value)
const startPosition = computed(() =>
  Math.max(0, Math.floor((scrollTop.value - itemsTop.value) / itemHeight.value) - renderAhead),
)
const offsetY = computed(() => startPosition.value * itemHeight.value)

const renderedItems = computed(() => {
  let count = Math.ceil(scrollerHeight.value / itemHeight.value) + 2 * renderAhead
  count = Math.min(items.value.length - startPosition.value, count)
  return items.value.slice(startPosition.value, startPosition.value + count)
})

const onScroll = (e: Event) =>
  requestAnimationFrame(() => {
    scrollTop.value = (e.target as HTMLElement).scrollTop
    itemsTop.value = itemsBox.value?.offsetTop ?? 0

    if (!scroller.value) {
      return
    }

    emit('scroll', e)

    if (scroller.value.scrollTop + scroller.value.clientHeight + itemHeight.value >= scroller.value.scrollHeight) {
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

const centeredTopOf = (index: number) => {
  itemsTop.value = itemsBox.value?.offsetTop ?? 0

  return Math.max(0, itemsTop.value + index * itemHeight.value - scrollerHeight.value / 2 + itemHeight.value / 2)
}

const scrollToIndex = (index: number) => {
  scroller.value?.scrollTo({ top: centeredTopOf(index), behavior: 'smooth' })
}

const jumpTo = (top: number) => {
  if (!scroller.value) {
    return
  }

  scroller.value.scrollTop = Math.max(0, top)
  scrollTop.value = scroller.value.scrollTop
}

const jumpToIndex = (index: number) => jumpTo(centeredTopOf(index))

defineExpose({ scrollToIndex, jumpTo, jumpToIndex, renderedItems, getScrollerElement: () => scroller.value })
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

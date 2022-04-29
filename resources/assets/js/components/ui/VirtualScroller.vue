<template>
  <div ref="scroller" class="virtual-scroller" @scroll.passive="onScroll">
    <div :style="{ height: `${totalHeight}px` }">
      <div :style="{ transform: `translateY(${offsetY}px)`}">
        <template v-for="item in renderedItems">
          <slot :item="item"></slot>
        </template>
      </div>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { computed, onMounted, onUnmounted, ref, toRefs } from 'vue'

const props = defineProps<{ items: any[], itemHeight: number }>()
const { items, itemHeight } = toRefs(props)

const scroller = ref<HTMLElement>()
const scrollerHeight = ref(0)
const renderAhead = 5
const scrollTop = ref(0)

const totalHeight = computed(() => items.value.length * itemHeight.value)
const startPosition = computed(() => Math.max(0, Math.floor(scrollTop.value / itemHeight.value) - renderAhead))
const offsetY = computed(() => startPosition.value * itemHeight.value)

const renderedItems = computed(() => {
  let count = Math.ceil(scrollerHeight.value / itemHeight.value) + 2 * renderAhead
  count = Math.min(items.value.length - startPosition.value, count)
  return items.value.slice(startPosition.value, startPosition.value + count)
})

const onScroll = e => requestAnimationFrame(() => (scrollTop.value = (e.target as HTMLElement).scrollTop))

const observer = new ResizeObserver(entries => entries.forEach(el => scrollerHeight.value = el.contentRect.height))

onMounted(() => {
  observer.observe(scroller.value!)
  scrollerHeight.value = scroller.value!.offsetHeight
})

onUnmounted(() => observer.unobserve(scroller.value!))
</script>

<style lang="scss" scoped>
.virtual-scroller {
  overflow: auto;
  will-change: transform;

  > div {
    overflow: hidden;
    will-change: transform;

    > div {
      will-change: transform;
    }
  }
}
</style>

<template>
  <div ref="container" :class="`as-${viewMode}`" class="grid gap-5 p-6">
    <slot />
  </div>
</template>

<script lang="ts" setup>
import { nextTick, ref, toRefs } from 'vue'

const props = withDefaults(defineProps<{ viewMode?: ViewMode }>(), {
  viewMode: 'thumbnails',
})

const container = ref<HTMLDivElement>()

const { viewMode } = toRefs(props)

const scrollToTop = async () => {
  await nextTick()

  container.value!.scrollTo?.({
    top: 0,
    behavior: 'smooth',
  })
}

defineExpose({
  scrollToTop,
})
</script>

<style lang="postcss" scoped>
div {
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  content-visibility: auto;
}

div.as-list {
  @apply gap-x-4 gap-y-3 content-start;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
}
</style>

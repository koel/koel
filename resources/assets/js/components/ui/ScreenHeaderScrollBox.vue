<template>
  <div ref="box" class="scroll-mask-b scroll-mask-t-from-100% overflow-auto">
    <ScreenHeaderSpace />
    <slot />
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import { useScreenHeaderScroller } from '@/composables/useScreenHeaderScroller'

import ScreenHeaderSpace from '@/components/ui/ScreenHeaderSpace.vue'

const box = ref<HTMLElement>()

useScreenHeaderScroller(() => box.value)
</script>

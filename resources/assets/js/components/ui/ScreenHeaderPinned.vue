<template>
  <div
    :class="{ 'transition-[top] duration-200 ease-out': screenHeader?.sliding.value }"
    :style="{ top: `${screenHeader?.columnHeaderTop.value ?? 0}px` }"
    class="screen-header-pinned sticky z-2"
  >
    <slot />
  </div>
</template>

<script lang="ts" setup>
import { inject } from 'vue'
import { ScrollAwayHeaderKey } from '@/config/symbols'

const screenHeader = inject(ScrollAwayHeaderKey, null)
</script>

<style lang="postcss" scoped>
.screen-header-pinned {
  background-color: color-mix(in srgb, var(--color-fg) 3%, var(--color-bg));
}
</style>

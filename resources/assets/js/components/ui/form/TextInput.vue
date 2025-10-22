<template>
  <input
    ref="el"
    v-model="value"
    :aria-label="ariaLabel"
    class="block text-base w-full px-3.5 py-2 rounded bg-k-bg-input text-k-fg-input border border-k-fg-10
    read-only:bg-gray-400 read-only:text-gray-900 disabled:bg-gray-400 disabled:text-gray-900 disabled:cursor-not-allowed"
    type="text"
  >
</template>

<script lang="ts" setup>
import { computed, ref, useAttrs } from 'vue'

const value = defineModel<string | number>()
const attrs = useAttrs()

// Aria label is optional, but if it's not provided, we'll use the name prop as a fallback.
// This is useful for both accessibility and testing, since getByRole('textbox', { name: '...' })
// looks for aria-label.
const ariaLabel = computed(() => String(attrs.ariaLabel || attrs.title || attrs.name))

const el = ref<HTMLInputElement>()

defineExpose({
  el,
})
</script>

<template>
  <button
    :aria-selected="selected"
    class="cursor-pointer relative px-5 py-3 rounded-t-md opacity-70 bg-k-fg-5 uppercase duration-200 hover:bg-k-fg-10
    aria-selected:transition-none aria-selected:text-k-fg aria-selected:bg-k-fg-10 aria-selected:opacity-100"
    role="tab"
    type="button"
    @click.prevent="emit('click')"
  >
    <slot />
  </button>
</template>

<script lang="ts" setup>
import { toRefs } from 'vue'

const props = defineProps<{ selected: boolean }>()
const emit = defineEmits<{ (e: 'click'): void }>()

const { selected } = toRefs(props)
</script>

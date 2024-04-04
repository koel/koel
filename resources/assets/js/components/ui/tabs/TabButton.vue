<template>
  <button
    :aria-selected="selected"
    role="tab"
    type="button"
    class="cursor-pointer relative px-5 py-3 rounded-t-md opacity-70 bg-white/5 uppercase text-k-text-secondary duration-200 hover:bg-white/10
    aria-selected:transition-none aria-selected:text-k-text-primary aria-selected:bg-white/10 aria-selected:opacity-100"
    @click.prevent="emit('click')"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
import { toRefs } from 'vue'

const props = defineProps<{ selected: boolean }>()
const { selected } = toRefs(props)

const emit = defineEmits<{ (e: 'click'): void }>()
</script>

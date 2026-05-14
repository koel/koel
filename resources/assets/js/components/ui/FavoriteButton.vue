<template>
  <button
    v-koel-tooltip
    :title
    class="relative transition-[color] duration-200 ease-in-out hover:text-k-fg"
    @click.prevent.stop="emit('toggle')"
  >
    <Icon :class="favorite && 'text-k-fg-70'" :icon="favorite ? faStar : faEmptyStar" size="sm" />
    <span class="sr-only">{{ title }}</span>
  </button>
</template>

<script setup lang="ts">
import { faStar } from '@fortawesome/free-solid-svg-icons'
import { faStar as faEmptyStar } from '@fortawesome/free-regular-svg-icons'
import { computed } from 'vue'

const props = defineProps<{ favorite: boolean }>()
const emit = defineEmits<{ (e: 'toggle'): void }>()

const title = computed(() => (props.favorite ? 'Undo Favorite' : 'Favorite'))
</script>

<style lang="postcss" scoped>
button::after {
  content: '';
  @apply absolute -inset-3;
}
</style>

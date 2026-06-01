<template>
  <button
    v-koel-tooltip
    :title
    class="relative transition-[color] duration-200 ease-in-out hover:text-k-love"
    :class="favorite && 'text-k-love'"
    @click.prevent.stop="emit('toggle')"
  >
    <Icon :icon="favorite ? faHeart : faEmptyHeart" size="sm" />
    <span class="sr-only">{{ title }}</span>
  </button>
</template>

<script setup lang="ts">
import { faHeart } from '@fortawesome/free-solid-svg-icons'
import { faHeart as faEmptyHeart } from '@fortawesome/free-regular-svg-icons'
import { computed } from 'vue'

const props = defineProps<{ favorite: boolean }>()
const emit = defineEmits<{ (e: 'toggle'): void }>()

const title = computed(() => (props.favorite ? 'Undo Favorite' : 'Favorite'))
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
button::after {
  content: '';
  @apply absolute -inset-3;
}
</style>

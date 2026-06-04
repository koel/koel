<template>
  <button v-koel-tooltip :title class="relative" @click.prevent.stop="emit('toggle')">
    <Icon :icon="favorite ? faHeart : faEmptyHeart" :size="iconSize" />
    <span class="sr-only">{{ title }}</span>
  </button>
</template>

<script setup lang="ts">
import { faHeart } from '@fortawesome/free-solid-svg-icons'
import { faHeart as faEmptyHeart } from '@fortawesome/free-regular-svg-icons'
import { computed } from 'vue'

const props = withDefaults(defineProps<{ favorite: boolean; size?: 'sm' | 'md' }>(), { size: 'sm' })
const emit = defineEmits<{ (e: 'toggle'): void }>()

const title = computed(() => (props.favorite ? 'Undo Favorite' : 'Favorite'))
const iconSize = computed(() => (props.size === 'md' ? undefined : props.size))
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';
button::after {
  content: '';
  @apply absolute -inset-3;
}
</style>

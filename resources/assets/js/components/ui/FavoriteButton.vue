<template>
  <button
    v-koel-tooltip
    :title
    class="transition-[color] duration-200 ease-in-out text-k-text-secondary hover:text-k-text-primary"
    @click.prevent.stop="emit('toggle')"
  >
    <Icon :class="favorite && 'text-k-highlight'" :icon="favorite ? faStar : faEmptyStar" size="sm" />
    <span class="sr-only">{{ title }}</span>
  </button>
</template>

<script setup lang="ts">
import { faStar } from '@fortawesome/free-solid-svg-icons'
import { faStar as faEmptyStar } from '@fortawesome/free-regular-svg-icons'
import { computed } from 'vue'

const props = defineProps<{ favorite: boolean }>()
const emit = defineEmits<{ (e: 'toggle'): void }>()

const title = computed(() => props.favorite ? 'Undo Favorite' : 'Favorite')
</script>

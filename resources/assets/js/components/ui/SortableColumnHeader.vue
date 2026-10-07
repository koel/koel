<template>
  <span role="button" tabindex="0" @click="emit('sort')" @keydown.enter.space.prevent="emit('sort')">
    <slot>{{ label }}</slot>
    <template v-if="active">
      <Icon v-if="order === 'asc'" :icon="faCaretUp" class="ml-2 text-k-highlight" />
      <Icon v-else :icon="faCaretDown" class="ml-2 text-k-highlight" />
    </template>
  </span>
</template>

<script lang="ts" setup>
import { faCaretDown, faCaretUp } from '@fortawesome/free-solid-svg-icons'

defineProps<{ active: boolean; order: SortOrder; label?: string }>()

const emit = defineEmits<{ (e: 'sort'): void }>()
</script>

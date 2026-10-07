<template>
  <span
    :role="sortable ? 'button' : undefined"
    :tabindex="sortable ? 0 : undefined"
    @click="requestSort"
    @keydown.enter.space.prevent="requestSort"
  >
    <slot>{{ label }}</slot>
    <template v-if="sortable && active">
      <Icon v-if="order === 'asc'" :icon="faCaretUp" class="ml-2 text-k-highlight" />
      <Icon v-else :icon="faCaretDown" class="ml-2 text-k-highlight" />
    </template>
  </span>
</template>

<script lang="ts" setup>
import { faCaretDown, faCaretUp } from '@fortawesome/free-solid-svg-icons'

const props = withDefaults(defineProps<{ active: boolean; order: SortOrder; label?: string; sortable?: boolean }>(), {
  label: undefined,
  sortable: true,
})

const emit = defineEmits<{ (e: 'sort'): void }>()

const requestSort = () => props.sortable && emit('sort')
</script>

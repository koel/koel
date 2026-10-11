<template>
  <li :class="isActive && 'active'" class="flex items-center gap-1 py-1.5 border-b border-k-fg-5 last:border-b-0">
    <span :title="name" class="name flex-1 truncate">{{ name }}</span>
    <button
      :aria-pressed="isActive"
      class="px-2 h-8 rounded-sm text-sm text-k-fg-70 hover:text-k-fg hover:bg-k-fg-5 aria-pressed:text-k-highlight"
      type="button"
      @click="apply"
    >
      Apply
    </button>
    <button
      v-koel-tooltip
      class="w-8 h-8 inline-grid place-items-center rounded-full text-k-fg-70 hover:text-k-danger hover:bg-k-fg-5"
      type="button"
      @click="remove"
    >
      <Trash2Icon :size="16" />
      <span class="sr-only">Delete</span>
    </button>
  </li>
</template>

<script lang="ts" setup>
import { Trash2Icon } from 'lucide-vue-next'
import { computed } from 'vue'
import { milkdropPresetStore } from '@/stores/milkdropPresetStore'

const props = defineProps<{ name: string }>()

const isActive = computed(() => milkdropPresetStore.state.selected === props.name)

const apply = () => milkdropPresetStore.select(props.name)
const remove = () => milkdropPresetStore.remove(props.name)
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

.active .name {
  @apply text-k-highlight;
}
</style>

<template>
  <li class="flex items-center gap-3 py-1.5 border-b border-k-fg-5 last:border-b-0">
    <span class="flex-1 truncate" :title="name">{{ name }}</span>
    <button
      v-koel-tooltip
      class="w-8 h-8 rounded-full text-k-fg-70 hover:text-k-danger hover:bg-k-fg-5"
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
import { milkdropPresetStore } from '@/stores/milkdropPresetStore'

const props = defineProps<{ name: string }>()

const remove = () => milkdropPresetStore.remove(props.name)
</script>

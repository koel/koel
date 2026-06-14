<template>
  <div
    :draggable="!!reorderable"
    class="flex items-center gap-3 px-3 py-2 rounded-md bg-k-bg-secondary hover:bg-k-fg-5 cursor-grab active:cursor-grabbing select-none transition"
    @dragstart="onDragStart"
  >
    <Icon :icon="faGripVertical" class="text-k-fg-50" />
    <span class="text-k-fg">{{ label }}</span>
  </div>
</template>

<script lang="ts" setup>
import { faGripVertical } from '@fortawesome/free-solid-svg-icons'
import { inject } from 'vue'
import { ReorderableItemKey } from '@/config/symbols'

defineProps<{ label: string }>()

const reorderable = inject(ReorderableItemKey, null)

const onDragStart = (event: DragEvent) => {
  if (!reorderable) {
    return
  }

  reorderable.onHeaderDragStart(event)
}
</script>

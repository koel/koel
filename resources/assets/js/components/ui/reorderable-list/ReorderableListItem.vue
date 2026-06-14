<template>
  <div
    ref="wrapperRef"
    class="reorderable-list-item relative"
    :class="{ 'reorderable-list-item--dragging': isDragging }"
    @dragover.prevent="onDragOver"
    @drop.prevent="onDrop"
  >
    <div class="reorderable-list-item__inner">
      <slot />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { provide, useTemplateRef } from 'vue'
import { ReorderableItemKey } from '@/config/symbols'

const props = defineProps<{
  id: string
  isDragging: boolean
}>()

const emit = defineEmits<{
  (e: 'dragstart', id: string, wrapper: HTMLElement, event: DragEvent): void
  (e: 'dragover', id: string, event: DragEvent): void
  (e: 'drop', id: string): void
}>()

const wrapperRef = useTemplateRef<HTMLElement>('wrapperRef')

provide(ReorderableItemKey, {
  onHeaderDragStart: (event: DragEvent) => {
    if (wrapperRef.value) {
      emit('dragstart', props.id, wrapperRef.value, event)
    }
  },
})

const onDragOver = (event: DragEvent) => {
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'move'
  }
  emit('dragover', props.id, event)
}

const onDrop = () => emit('drop', props.id)
</script>

<style lang="postcss" scoped>
.reorderable-list-item--dragging {
  outline: 1px dashed var(--color-highlight);
  outline-offset: 6px;
}

.reorderable-list-item--dragging .reorderable-list-item__inner {
  opacity: 0.35;
}
</style>

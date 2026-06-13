<template>
  <div
    ref="wrapperRef"
    class="home-block-sortable relative"
    :class="{ 'home-block-sortable--dragging': isDragging }"
    @dragover.prevent="onDragOver"
    @drop.prevent="onDrop"
  >
    <div class="home-block-sortable__inner">
      <slot />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { provide, useTemplateRef } from 'vue'
import { HomeBlockSortableKey } from '@/config/symbols'

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

provide(HomeBlockSortableKey, {
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
.home-block-sortable--dragging {
  outline: 1px dashed var(--color-highlight);
  outline-offset: 6px;
}

.home-block-sortable--dragging .home-block-sortable__inner {
  opacity: 0.35;
}
</style>

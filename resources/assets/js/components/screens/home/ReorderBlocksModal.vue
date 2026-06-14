<template>
  <div
    v-koel-focus
    class="reorder-blocks-modal flex flex-col"
    data-testid="reorder-blocks-modal"
    tabindex="0"
    @keydown.esc="close"
  >
    <header>
      <h1>Reorder Home Blocks</h1>
    </header>

    <main class="space-y-1">
      <div
        v-for="block in orderedBlocks"
        :key="block.id"
        :draggable="true"
        class="group flex transition-all items-center gap-2 pr-3 py-2 rounded-sm bg-k-bg-secondary hover:bg-k-fg-5 hover:pl-3 cursor-grab active:cursor-grabbing active:text-k-highlight select-none"
        :class="{ 'opacity-40': draggedId === block.id }"
        @dragstart="onDragStart(block.id, $event)"
        @dragover.prevent="onDragOver(block.id, $event)"
        @dragend="onDragEnd"
        @drop.prevent
      >
        <GripVerticalIcon class="w-4 h-4 text-k-fg-50" />
        {{ block.label }}
      </div>
    </main>

    <footer>
      <Btn @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { isEqual } from 'lodash-es'
import { GripVerticalIcon } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { preferenceStore } from '@/stores/preferenceStore'

import Btn from '@/components/ui/form/Btn.vue'

interface BlockSummary {
  id: string
  label: string
}

const props = defineProps<{ blocks: BlockSummary[] }>()

const emit = defineEmits<{ (e: 'close'): void }>()

const bySavedOrder = (saved: readonly string[]) => (a: BlockSummary, b: BlockSummary) => {
  const positionOf = (id: string) => {
    const i = saved.indexOf(id)
    return i === -1 ? Infinity : i
  }

  return positionOf(a.id) - positionOf(b.id)
}

const draggedId = ref<string | null>(null)
const orderIds = ref<string[]>(
  [...props.blocks].sort(bySavedOrder(preferenceStore.home_blocks_order ?? [])).map(b => b.id),
)

const orderedBlocks = computed(() => orderIds.value.map(id => props.blocks.find(b => b.id === id)!))

const onDragStart = (id: string, event: DragEvent) => {
  if (!event.dataTransfer) {
    return
  }

  event.dataTransfer.effectAllowed = 'move'
  draggedId.value = id
}

const onDragOver = (targetId: string, event: DragEvent) => {
  if (draggedId.value === null || draggedId.value === targetId) {
    return
  }

  const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
  const insertBefore = event.clientY < rect.top + rect.height / 2

  const next = orderIds.value.filter(id => id !== draggedId.value)
  const targetIndex = next.indexOf(targetId)

  if (targetIndex === -1) {
    return
  }

  next.splice(insertBefore ? targetIndex : targetIndex + 1, 0, draggedId.value)

  if (!isEqual(next, orderIds.value)) {
    orderIds.value = next
  }
}

const onDragEnd = () => {
  if (draggedId.value === null) {
    return
  }

  if (!isEqual(orderIds.value, preferenceStore.home_blocks_order ?? [])) {
    preferenceStore.home_blocks_order = [...orderIds.value]
  }

  draggedId.value = null
}

const close = () => emit('close')
</script>

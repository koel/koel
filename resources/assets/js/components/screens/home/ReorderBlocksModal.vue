<template>
  <div
    v-koel-focus
    class="reorder-blocks-modal w-[min(360px,calc(100vw-2rem))] flex flex-col"
    data-testid="reorder-blocks-modal"
    tabindex="0"
    @keydown.esc="close"
  >
    <header>
      <h1>Reorder home blocks</h1>
    </header>

    <main class="space-y-2">
      <ReorderableList :items="orderedBlocks" @reorder="onReorder">
        <template #default="{ item }">
          <BlockReorderRow :label="(item as { id: string; label: string }).label" />
        </template>
      </ReorderableList>
    </main>

    <footer>
      <Btn @click.prevent="close">Close</Btn>
    </footer>
  </div>
</template>

<script lang="ts" setup>
import { computed } from 'vue'
import { preferenceStore } from '@/stores/preferenceStore'

import Btn from '@/components/ui/form/Btn.vue'
import ReorderableList from '@/components/ui/reorderable-list/ReorderableList.vue'
import BlockReorderRow from '@/components/screens/home/BlockReorderRow.vue'

const props = defineProps<{ blocks: { id: string; label: string }[] }>()

const emit = defineEmits<{ (e: 'close'): void }>()

const bySavedOrder = (saved: readonly string[]) => (a: { id: string }, b: { id: string }) => {
  const positionOf = (id: string) => {
    const i = saved.indexOf(id)
    return i === -1 ? Infinity : i
  }

  return positionOf(a.id) - positionOf(b.id)
}

const orderedBlocks = computed(() => [...props.blocks].sort(bySavedOrder(preferenceStore.home_blocks_order ?? [])))

const onReorder = (ids: string[]) => {
  preferenceStore.home_blocks_order = ids
}

const close = () => emit('close')
</script>

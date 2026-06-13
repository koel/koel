<template>
  <div>
    <header class="flex items-center justify-between gap-3 mb-8">
      <h3
        :draggable="!!sortable"
        :class="[
          'text-2xl font-thin text-k-fg min-w-0',
          sortable ? 'cursor-grab active:cursor-grabbing select-none' : '',
        ]"
        @dragstart="onHeaderDragStart"
      >
        <slot name="header" />
      </h3>
      <div ref="actionsEl" class="flex items-center gap-2 shrink-0">
        <slot name="actions" />
      </div>
    </header>

    <slot />
  </div>
</template>

<script lang="ts" setup>
import { inject, provide, useTemplateRef } from 'vue'
import { HomeBlockActionsKey, SortableItemKey } from '@/config/symbols'

const sortable = inject(SortableItemKey, null)

const actionsEl = useTemplateRef<HTMLElement>('actionsEl')
provide(HomeBlockActionsKey, actionsEl)

const onHeaderDragStart = (event: DragEvent) => {
  if (!sortable) {
    return
  }

  sortable.onHeaderDragStart(event)
}
</script>

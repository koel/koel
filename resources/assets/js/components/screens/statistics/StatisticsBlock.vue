<template>
  <section class="flex flex-col gap-4">
    <header class="flex items-center justify-between gap-3">
      <h3 class="text-xl font-semibold text-k-fg min-w-0 truncate">
        <slot name="header" />
      </h3>
      <div ref="actionsEl" class="flex items-center gap-2 shrink-0" />
    </header>

    <slot />
  </section>
</template>

<script lang="ts" setup>
import { provide, useTemplateRef } from 'vue'
import { BlockActionsHostKey } from '@/config/symbols'

const actionsEl = useTemplateRef<HTMLElement>('actionsEl')
provide(BlockActionsHostKey, actionsEl)
</script>

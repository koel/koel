<template>
  <label class="flex text-base items-center gap-2 relative cursor-pointer">
    <input v-bind="$attrs" type="file" class="opacity-0 appearance-none absolute inset-0 z-10">
    <span class="border border-px border-white/20 px-1.5 py-1 rounded pointer-events-none">
      <Icon :icon="faPaperclip" fixed-width />
    </span>
    <span class="text-1 pointer-events-none">
      <slot>Select a file…</slot>
    </span>
  </label>
</template>

<script setup lang="ts">
import { faPaperclip } from '@fortawesome/free-solid-svg-icons'

defineOptions({ inheritAttrs: false })
</script>

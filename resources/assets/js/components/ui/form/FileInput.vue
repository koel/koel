<template>
  <label class="flex text-base cursor-pointer items-center gap-2">
    <input v-bind="$attrs" type="file" class="hidden">
    <span class="border border-px border-white/20 px-1.5 py-1 rounded">
      <Icon :icon="faPaperclip" fixed-width />
    </span>
    <span class="text-1">
      <slot>Select a file…</slot>
    </span>
  </label>
</template>

<script setup lang="ts">
import { faPaperclip } from '@fortawesome/free-solid-svg-icons'

defineOptions({ inheritAttrs: false })
</script>

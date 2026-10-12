<template>
  <slot />
</template>

<script lang="ts" setup>
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts'

useKeyboardShortcuts().listenForShortcuts()
</script>

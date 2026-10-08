<template>
  <slot />
</template>

<script lang="ts" setup>
import { onKeyStroke } from '@vueuse/core'
import type { KeyboardShortcut } from '@/composables/useKeyboardShortcuts'
import { isShortcutAvailable, useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts'

const isTypingOrInDialog = (target: HTMLElement) =>
  target.isContentEditable ||
  target.matches('input, select, textarea, button, [role="button"], [role="checkbox"]') ||
  Boolean(target.closest('dialog'))

const listenForShortcut = (shortcut: KeyboardShortcut) =>
  onKeyStroke(shortcut.key, event => {
    if (event.altKey || event.ctrlKey || event.metaKey) {
      return
    }

    if (isTypingOrInDialog(event.target as HTMLElement) || !isShortcutAvailable(shortcut)) {
      return
    }

    event.preventDefault()
    shortcut.run(event)
  })

useKeyboardShortcuts().shortcuts.forEach(listenForShortcut)
</script>

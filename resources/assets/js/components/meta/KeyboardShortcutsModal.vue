<template>
  <div v-koel-focus class="md:w-[640px]" data-testid="keyboard-shortcuts" tabindex="0" @keydown.esc="close">
    <header class="items-center gap-4">
      <h1 class="flex-1">Keyboard Shortcuts</h1>
      <button
        aria-label="Close"
        class="w-8 h-8 rounded-full text-k-fg-70 hover:text-k-fg hover:bg-k-fg-5"
        title="Close"
        type="button"
        @click="close"
      >
        <Icon :icon="faTimes" fixed-width />
      </button>
    </header>

    <main class="grid md:grid-cols-2 gap-x-8 gap-y-2">
      <div v-for="(column, columnIndex) in columns" :key="columnIndex" class="flex flex-col gap-4">
        <section v-for="group in column" :key="group.name" :data-testid="`shortcut-group-${group.name}`">
          <h2 class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-k-fg-50">{{ group.name }}</h2>
          <dl>
            <div
              v-for="shortcut in group.shortcuts"
              :key="shortcut.label"
              class="flex items-center justify-between gap-3 py-1.5 border-b border-k-fg-5 last:border-b-0"
            >
              <dt class="text-k-fg-70">{{ shortcut.label }}</dt>
              <dd class="flex items-center gap-1 flex-none">
                <template v-for="(key, keyIndex) in shortcut.keys" :key="key">
                  <span v-if="shortcut.isKeyRange && keyIndex > 0" class="text-k-fg-50">–</span>
                  <kbd>{{ key }}</kbd>
                </template>
              </dd>
            </div>
          </dl>
        </section>
      </div>
    </main>
  </div>
</template>

<script lang="ts" setup>
import { faTimes } from '@fortawesome/free-solid-svg-icons'
import type { KeyboardShortcutGroup } from '@/composables/useKeyboardShortcuts'
import { isShortcutAvailable, useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts'

const emit = defineEmits<{ (e: 'close'): void }>()

const columnLayout: KeyboardShortcutGroup[][] = [
  ['Playback', 'View'],
  ['Sound', 'Go to'],
]

const availableShortcuts = useKeyboardShortcuts().shortcuts.filter(isShortcutAvailable)

const columns = columnLayout.map(groupNames =>
  groupNames
    .map(name => ({ name, shortcuts: availableShortcuts.filter(shortcut => shortcut.group === name) }))
    .filter(group => group.shortcuts.length),
)

const close = () => emit('close')
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

kbd {
  @apply inline-grid place-items-center min-w-[26px] h-[26px] px-2 font-mono text-sm font-semibold text-k-fg bg-k-fg-10 border border-b-2 border-k-fg-10 rounded-md;
}
</style>

<template>
  <div v-koel-focus class="md:w-[640px]" data-testid="keyboard-shortcuts" tabindex="0" @keydown.esc="close">
    <header class="items-center gap-4">
      <h1 class="flex-1">Keyboard Shortcuts</h1>
      <button class="w-8 h-8 rounded-full text-k-fg-70 hover:text-k-fg hover:bg-k-fg-5" type="button" @click="close">
        <Icon :icon="faTimes" fixed-width />
        <span class="sr-only">Close</span>
      </button>
    </header>

    <main class="grid md:grid-cols-2 gap-x-8 gap-y-2">
      <div v-for="(column, columnIndex) in columns" :key="columnIndex" class="flex flex-col gap-4">
        <section v-for="group in column" :key="group.name" :data-testid="`shortcut-group-${group.name}`">
          <h2 class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-k-fg-50">{{ group.heading }}</h2>
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
                  <span v-if="shortcut.isKeyChoice && keyIndex > (shortcut.firstChoiceIndex ?? 0)" class="text-k-fg-50">
                    /
                  </span>
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
import type { KeyboardShortcut, KeyboardShortcutGroup } from '@/composables/useKeyboardShortcuts'
import { isShortcutAvailable, useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts'
import { commonStore } from '@/stores/commonStore'

const emit = defineEmits<{ (e: 'close'): void }>()

const columnLayout: KeyboardShortcutGroup[][] = [
  ['Playback', 'View', 'Sound'],
  ['Lists', 'Go to'],
]

type ListedShortcut = Pick<KeyboardShortcut, 'group' | 'label' | 'keys' | 'isKeyRange' | 'isKeyChoice'> & {
  firstChoiceIndex?: number
}

const listShortcuts: ListedShortcut[] = [
  { group: 'Lists', label: 'Navigate up or down', keys: ['↑', '↓'], isKeyChoice: true },
  { group: 'Lists', label: 'Extend the selection', keys: ['Shift', '↑', '↓'], isKeyChoice: true, firstChoiceIndex: 1 },
  { group: 'Lists', label: 'Play, queue or open the selection', keys: ['Enter'] },
  { group: 'Lists', label: 'Select all', keys: ['Cmd/Ctrl', 'A'] },
  ...(commonStore.state.uses_media_browser
    ? [{ group: 'Lists' as const, label: 'Parent folder', keys: ['Backspace'] }]
    : []),
]

const listsHeading = commonStore.state.uses_media_browser ? 'Song Lists and Media Browser' : 'Song Lists'

const availableShortcuts: ListedShortcut[] = [
  ...useKeyboardShortcuts().shortcuts.filter(isShortcutAvailable),
  ...listShortcuts,
]

const columns = columnLayout.map(groupNames =>
  groupNames
    .map(name => ({
      name,
      heading: name === 'Lists' ? listsHeading : name,
      shortcuts: availableShortcuts.filter(shortcut => shortcut.group === name),
    }))
    .filter(group => group.shortcuts.length),
)

const close = () => emit('close')
</script>

<style lang="postcss" scoped>
@reference '@css/app.pcss';

kbd {
  @apply inline-grid place-items-center min-w-[22px] h-[22px] px-1 font-mono text-xs text-k-fg bg-k-fg-10 border border-b-2 border-k-fg-10 rounded-md;
}
</style>

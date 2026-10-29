import type { KeyFilter } from '@vueuse/core'
import { onKeyStroke } from '@vueuse/core'
import type { RouteName } from '@/config/routes'
import { eventBus } from '@/utils/eventBus'
import { defineAsyncComponent } from '@/utils/helpers'
import { isAudioContextSupported, isFullscreenSupported } from '@/utils/supports'
import { socketService } from '@/services/socketService'
import { volumeManager } from '@/services/volumeManager'
import { playback } from '@/services/playbackManager'
import { commonStore } from '@/stores/commonStore'
import { playableStore } from '@/stores/playableStore'
import { queueStore } from '@/stores/queueStore'
import { useModal } from '@/composables/useModal'
import { useRouter } from '@/composables/useRouter'

export type KeyboardShortcutGroup = 'Playback' | 'View' | 'Sound' | 'Go to' | 'Lists'

export interface KeyboardShortcut {
  group: KeyboardShortcutGroup
  label: string
  keys: string[]
  isKeyRange?: boolean
  isKeyChoice?: boolean
  key: KeyFilter
  run: (event: KeyboardEvent) => void
  isAvailable?: () => boolean
}

const Equalizer = defineAsyncComponent(() => import('@/components/ui/equalizer/Equalizer.vue'))
const KeyboardShortcutsModal = defineAsyncComponent(() => import('@/components/meta/KeyboardShortcutsModal.vue'))

const isDigit = (event: KeyboardEvent) => /^\d$/.test(event.key)
const letterKey =
  (letter: string, { shift = false } = {}) =>
  (event: KeyboardEvent) =>
    event.key.toLowerCase() === letter && event.shiftKey === shift

export const isShortcutAvailable = (shortcut: KeyboardShortcut) => shortcut.isAvailable?.() ?? true

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

export const useKeyboardShortcuts = () => {
  const { go, isCurrentScreen, url } = useRouter()
  const { openModal } = useModal()

  const toggleScreen = (screen: ScreenName, routeName: RouteName) => go(isCurrentScreen(screen) ? -1 : url(routeName))

  const jumpToPercentOfCurrentSong = (event: KeyboardEvent) => {
    const current = queueStore.current

    if (!current) {
      return
    }

    playback('current')?.seekTo((current.length * Number(event.key)) / 10)
  }

  const toggleFavoriteOfCurrentSong = () => {
    if (!queueStore.current) {
      return
    }

    playableStore.toggleFavorite(queueStore.current)
    socketService.broadcast('SOCKET_STREAMABLE', queueStore.current)
  }

  const shortcuts: KeyboardShortcut[] = [
    { group: 'Playback', label: 'Play or pause', keys: ['Space'], key: ' ', run: () => playback('current')?.toggle() },
    {
      group: 'Playback',
      label: 'Next/Previous song',
      isKeyChoice: true,
      keys: ['J', 'K'],
      key: event => letterKey('j')(event) || letterKey('k')(event),
      run: event =>
        event.key.toLowerCase() === 'j' ? playback('current')?.playNext() : playback('current')?.playPrev(),
    },
    {
      group: 'Playback',
      label: 'Forward/Backward 10 seconds',
      isKeyChoice: true,
      keys: ['→', '←'],
      key: ['ArrowRight', 'ArrowLeft'],
      run: event => (event.key === 'ArrowRight' ? playback('current')?.forward(10) : playback('current')?.rewind(10)),
    },
    {
      group: 'Playback',
      label: 'Jump to 0–90%',
      keys: ['0', '9'],
      isKeyRange: true,
      key: isDigit,
      run: jumpToPercentOfCurrentSong,
    },
    {
      group: 'Playback',
      label: 'Change repeat mode',
      keys: ['R'],
      key: letterKey('r'),
      run: () => playback('current')?.rotateRepeatMode(),
    },
    {
      group: 'Playback',
      label: 'Toggle favorite the current song',
      keys: ['L'],
      key: letterKey('l'),
      run: toggleFavoriteOfCurrentSong,
    },
    {
      group: 'View',
      label: 'Toggle visualizer',
      keys: ['V'],
      key: letterKey('v'),
      run: () => toggleScreen('Visualizer', 'visualizer'),
    },
    {
      group: 'View',
      label: 'Toggle fullscreen',
      keys: ['Shift', 'F'],
      key: letterKey('f', { shift: true }),
      run: () => eventBus.emit('FULLSCREEN_TOGGLE'),
      isAvailable: isFullscreenSupported,
    },
    {
      group: 'Sound',
      label: 'Volume up/down',
      isKeyChoice: true,
      keys: ['↑', '↓'],
      key: ['ArrowUp', 'ArrowDown'],
      run: event => (event.key === 'ArrowUp' ? volumeManager.increase() : volumeManager.decrease()),
    },
    {
      group: 'Sound',
      label: 'Mute or unmute',
      keys: ['M'],
      key: letterKey('m'),
      run: () => volumeManager.toggleMute(),
    },
    {
      group: 'Sound',
      label: 'Open equalizer',
      keys: ['E'],
      key: letterKey('e'),
      run: () => openModal<'EQUALIZER'>(Equalizer),
      isAvailable: () => isAudioContextSupported,
    },
    {
      group: 'Go to',
      label: 'Search',
      keys: ['F'],
      key: letterKey('f'),
      run: () => eventBus.emit('FOCUS_SEARCH_FIELD'),
    },
    {
      group: 'Go to',
      label: 'Toggle Queue',
      keys: ['Q'],
      key: letterKey('q'),
      run: () => toggleScreen('Queue', 'queue'),
    },
    { group: 'Go to', label: 'Home', keys: ['H'], key: letterKey('h'), run: () => go(url('home')) },
    { group: 'Go to', label: 'Settings', keys: [','], key: ',', run: () => go(url('settings')) },
    {
      group: 'Go to',
      label: 'Toggle AI Assistant',
      keys: ['/'],
      key: '/',
      run: () => toggleScreen('AI', 'ai'),
      isAvailable: () => commonStore.state.uses_ai,
    },
    {
      group: 'Go to',
      label: 'This list',
      keys: ['?'],
      key: '?',
      run: () => openModal<'KEYBOARD_SHORTCUTS'>(KeyboardShortcutsModal),
    },
  ]

  const listenForShortcuts = () => shortcuts.forEach(listenForShortcut)

  return { shortcuts, listenForShortcuts }
}

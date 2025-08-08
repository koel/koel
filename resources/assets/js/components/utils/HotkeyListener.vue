<template>
  <slot />
</template>

<script lang="ts" setup>
import type { KeyFilter } from '@vueuse/core'
import { onKeyStroke as baseOnKeyStroke } from '@vueuse/core'
import { eventBus } from '@/utils/eventBus'
import { socketService } from '@/services/socketService'
import { volumeManager } from '@/services/volumeManager'
import { queueStore } from '@/stores/queueStore'
import { useRouter } from '@/composables/useRouter'
import { playableStore } from '@/stores/playableStore'
import { playback } from '@/services/playbackManager'

const { isCurrentScreen, go, url } = useRouter()

const onKeyStroke = (key: KeyFilter, callback: (e: KeyboardEvent) => void) => {
  baseOnKeyStroke(key, e => {
    if (e.altKey || e.ctrlKey || e.metaKey) {
      return
    }

    if (e.target instanceof HTMLInputElement
      || e.target instanceof HTMLTextAreaElement
      || e.target instanceof HTMLButtonElement
    ) {
      return
    }

    const role = (e.target as HTMLElement).getAttribute('role')
    if (role === 'button' || role === 'checkbox') {
      return
    }

    e.preventDefault()
    callback(e)
  })
}

onKeyStroke('f', () => eventBus.emit('FOCUS_SEARCH_FIELD'))
onKeyStroke('j', () => playback('current')?.playNext())
onKeyStroke('k', () => playback('current')?.playPrev())
onKeyStroke(' ', () => playback('current')?.toggle())
onKeyStroke('r', () => playback('current')?.rotateRepeatMode())
onKeyStroke('q', () => go(isCurrentScreen('Queue') ? -1 : url('queue')))
onKeyStroke('h', () => go(url('home')))

onKeyStroke('ArrowRight', () => playback('current')?.forward(10))
onKeyStroke('ArrowLeft', () => playback('current')?.rewind(-10))
onKeyStroke('ArrowUp', () => volumeManager.increase())
onKeyStroke('ArrowDown', () => volumeManager.decrease())
onKeyStroke('m', () => volumeManager.toggleMute())

onKeyStroke('l', () => {
  if (!queueStore.current) {
    return
  }
  playableStore.toggleFavorite(queueStore.current)
  socketService.broadcast('SOCKET_STREAMABLE', queueStore.current)
})
</script>

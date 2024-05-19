<template>
  <slot />
</template>

<script lang="ts" setup>
import { KeyFilter, onKeyStroke as baseOnKeyStroke } from '@vueuse/core'
import { eventBus } from '@/utils'
import { playbackService, socketService, volumeManager } from '@/services'
import { favoriteStore, queueStore } from '@/stores'
import { useRouter } from '@/composables'

const { go } = useRouter()

const onKeyStroke = (key: KeyFilter, callback: (e: KeyboardEvent) => void) => {
  baseOnKeyStroke(key, e => {
    if (e.altKey || e.ctrlKey || e.metaKey) return

    if (e.target instanceof HTMLInputElement
      || e.target instanceof HTMLTextAreaElement
      || e.target instanceof HTMLButtonElement
    ) return

    const role = (e.target as HTMLElement).getAttribute('role')
    if (role === 'button' || role === 'checkbox') return

    e.preventDefault()
    callback(e)
  })
}

onKeyStroke('f', () => eventBus.emit('FOCUS_SEARCH_FIELD'))
onKeyStroke('j', () => playbackService.playNext())
onKeyStroke('k', () => playbackService.playPrev())
onKeyStroke(' ', () => playbackService.toggle())
onKeyStroke('r', () => playbackService.rotateRepeatMode())
onKeyStroke('q', () => go('queue'))

onKeyStroke('ArrowRight', () => playbackService.seekBy(10))
onKeyStroke('ArrowLeft', () => playbackService.seekBy(-10))
onKeyStroke('ArrowUp', () => volumeManager.increase())
onKeyStroke('ArrowDown', () => volumeManager.decrease())
onKeyStroke('m', () => volumeManager.toggleMute())

onKeyStroke('l', () => {
  if (!queueStore.current) return
  favoriteStore.toggleOne(queueStore.current)
  socketService.broadcast('SOCKET_SONG', queueStore.current)
})
</script>

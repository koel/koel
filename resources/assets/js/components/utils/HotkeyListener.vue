<template>
  <slot />
</template>

<script lang="ts" setup>
import { KeyFilter, onKeyStroke as baseOnKeyStroke } from '@vueuse/core'
import { eventBus } from '@/utils'
import { playbackService, socketService } from '@/services'
import { favoriteStore, queueStore } from '@/stores'

const onKeyStroke = (key: KeyFilter, callback: (e: KeyboardEvent) => void) => {
  baseOnKeyStroke(key, e => {
    if ( (e.target instanceof HTMLInputElement || e.target instanceof HTMLTextAreaElement)) return
    e.preventDefault()
    callback(e)
  })
}

onKeyStroke('f', () => eventBus.emit('FOCUS_SEARCH_FIELD'))
onKeyStroke('j', () => playbackService.playNext())
onKeyStroke('k', () => playbackService.playPrev())
onKeyStroke(' ', () => playbackService.toggle())

onKeyStroke('l', () => {
  if (!queueStore.current) return
  favoriteStore.toggleOne(queueStore.current)
  socketService.broadcast('SOCKET_SONG', queueStore.current)
})
</script>

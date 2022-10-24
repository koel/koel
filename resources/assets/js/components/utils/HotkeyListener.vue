<template>
  <GlobalEvents
    @keydown.space="togglePlayback"
    @keydown.j="playNext"
    @keydown.k="playPrev"
    @keydown.f="search"
    @keydown.l="toggleLike"
  />
</template>

<script lang="ts" setup>
import { GlobalEvents } from 'vue-global-events'
import { eventBus } from '@/utils'
import { playbackService, socketService } from '@/services'
import { favoriteStore, queueStore } from '@/stores'

const togglePlayback = (e: KeyboardEvent) => {
  if (
    !(e.target instanceof Document) &&
    (e.target as Element).matches('input, textarea, button, select') &&
    !(e.target as Element).matches('input[type=range]')
  ) {
    return true
  }

  e.preventDefault()
  playbackService.toggle()

  return false
}

/**
 * Play the previous song when user presses K.
 */
const playPrev = (e: KeyboardEvent) => {
  if (!(e.target instanceof Document) && (e.target as Element).matches('input, select, textarea')) {
    return true
  }

  e.preventDefault()
  playbackService.playPrev()

  return false
}

/**
 * Play the next song when user presses J.
 */
const playNext = (e: KeyboardEvent) => {
  if (!(e.target instanceof Document) && (e.target as Element).matches('input, select, textarea')) {
    return true
  }

  e.preventDefault()
  playbackService.playNext()

  return false
}

/**
 * Put focus into the search field when user presses F.
 */
const search = (e: KeyboardEvent) => {
  if (
    !(e.target instanceof Document) &&
    (e.target as Element).matches('input, select, textarea') && !(e.target as Element).matches('input[type=range]')
  ) {
    return true
  }

  if (e.metaKey || e.ctrlKey) {
    return true
  }

  e.preventDefault()
  eventBus.emit('FOCUS_SEARCH_FIELD')

  return false
}

/**
 * Like/unlike the current song when use presses L.
 */
const toggleLike = (e: KeyboardEvent) => {
  if (!(e.target instanceof Document) && (e.target as Element).matches('input, select, textarea')) {
    return true
  }

  if (!queueStore.current) {
    return false
  }

  favoriteStore.toggleOne(queueStore.current)
  socketService.broadcast('SOCKET_SONG', queueStore.current)

  return false
}
</script>

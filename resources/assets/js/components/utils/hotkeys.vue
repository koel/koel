<template>
  <global-events
    @keydown.space = "togglePlayback"
    @keydown.j = "playNext"
    @keydown.k = "playPrev"
    @keydown.f = "search"
    @keydown.l = "toggleLike"
    @keydown.mediaPrev = "playPrev"
    @keydown.mediaNext = "playNext"
    @keydown.mediaToggle = "togglePlayback"
  />
</template>

<script lang="ts">
import Vue from 'vue'
import GlobalEvents from 'vue-global-events'
import { $, eventBus, noop } from '@/utils'
import { events as eventNames } from '@/config'
import { playback, socket } from '@/services'
import { queueStore, favoriteStore, songStore } from '@/stores'

let ipc: any, events: any

if (KOEL_ENV === 'app') {
  ipc = require('electron').ipcRenderer
  events = require('&/events').default
}

// Register our custom key codes
Vue.config.keyCodes = {
  a: 65,
  j: 74,
  k: 75,
  f: 70,
  l: 76,
  mediaNext: 176,
  mediaPrev: 177,
  mediaToggle: 179
}

/**
 * Listen to the global shortcuts (media keys).
 * Only works in the app.
 */
let listenToGlobalShortcuts = noop

if (KOEL_ENV === 'app') {
  listenToGlobalShortcuts = (): void => {
    const mediaFunctionMap = {
      MediaNextTrack: () => playback.playNext(),
      MediaPreviousTrack: () => playback.playPrev(),
      MediaStop: () => playback.stop(),
      MediaPlayPause: () => playback.toggle()
    } as { [propName: string]: Function }

    ipc.on('GLOBAL_SHORTCUT', (e: any, msg: string) => msg in mediaFunctionMap && mediaFunctionMap[msg]())
  }
}

export default Vue.extend({
  components: {
    GlobalEvents
  },

  methods: {
    /**
     * Toggle playback when user presses Space key.
     */
    togglePlayback: (e: KeyboardEvent): boolean => {
      if (
        !(e.target instanceof Document) &&
        $.is(e.target as Element, 'input, textarea, button, select') &&
        !$.is(e.target as Element, 'input[type=range]')
      ) {
        return true
      }

      e.preventDefault()
      playback.toggle()

      return false
    },

    /**
     * Play the previous song when user presses K.
     */
    playPrev: (e: KeyboardEvent): boolean => {
      if (
        !(e.target instanceof Document) &&
        $.is(e.target as Element, 'input, select, textarea')
      ) {
        return true
      }

      e.preventDefault()
      playback.playPrev()

      return false
    },

    /**
     * Play the next song when user presses J.
     */
    playNext: (e: KeyboardEvent): boolean => {
      if (
        !(e.target instanceof Document) &&
        $.is(e.target as Element, 'input, select, textarea')
      ) {
        return true
      }

      e.preventDefault()
      playback.playNext()

      return false
    },

    /**
     * Put focus into the search field when user presses F.
     */
    search: (e: KeyboardEvent): boolean => {
      if (
        !(e.target instanceof Document) &&
        ($.is(e.target as Element, 'input, select, textarea') &&
        !$.is(e.target as Element, 'input[type=range]'))
      ) {
        return true
      }

      if (e.metaKey || e.ctrlKey) {
        return true
      }

      e.preventDefault()
      eventBus.emit(eventNames.FOCUS_SEARCH_FIELD)

      return false
    },

    /**
     * Like/unlike the current song when use presses L.
     */
    toggleLike: (e: KeyboardEvent): boolean => {
      if (
        !(e.target instanceof Document) &&
        $.is(e.target as Element, 'input, select, textarea')
      ) {
        return true
      }

      if (!queueStore.current) {
        return false
      }

      favoriteStore.toggleOne(queueStore.current)
      socket.broadcast(eventNames.SOCKET_SONG, songStore.generateDataToBroadcast(queueStore.current))
      return false
    }
  },

  created: (): void => {
    if (KOEL_ENV === 'app') {
      listenToGlobalShortcuts()
    }
  }
})
</script>

<template>
  <footer
    id="mainFooter"
    ref="root"
    @contextmenu.prevent="requestContextMenu"
    @mousemove="showControls"
  >
    <AudioPlayer v-show="song" />

    <div class="fullscreen-backdrop" :style="styles" />

    <div class="wrapper">
      <SongInfo />
      <PlaybackControls />
      <ExtraControls />
    </div>
  </footer>
</template>

<script lang="ts" setup>
import { throttle } from 'lodash'
import { computed, nextTick, ref, watch } from 'vue'
import { eventBus, isAudioContextSupported, requireInjection } from '@/utils'
import { CurrentSongKey } from '@/symbols'
import { artistStore, preferenceStore } from '@/stores'
import { audioService, playbackService } from '@/services'

import AudioPlayer from '@/components/layout/app-footer/AudioPlayer.vue'
import SongInfo from '@/components/layout/app-footer/FooterSongInfo.vue'
import ExtraControls from '@/components/layout/app-footer/FooterExtraControls.vue'
import PlaybackControls from '@/components/layout/app-footer/FooterPlaybackControls.vue'
import { useFullscreen } from '@vueuse/core'

const song = requireInjection(CurrentSongKey, ref())
let hideControlsTimeout: number

const root = ref<HTMLElement>()
const artist = ref<Artist>()

const requestContextMenu = (event: MouseEvent) => {
  if (document.fullscreenElement) return
  song.value && eventBus.emit('SONG_CONTEXT_MENU_REQUESTED', event, song.value)
}

watch(song, async () => {
  if (!song.value) return
  artist.value = await artistStore.resolve(song.value.artist_id)
})

const styles = computed(() => {
  const src = artist.value?.image ?? song.value?.album_cover

  return {
    backgroundImage: src ? `url(${src})` : 'none'
  }
})

const initPlaybackRelatedServices = async () => {
  const plyrWrapper = document.querySelector<HTMLElement>('.plyr')

  if (!plyrWrapper) {
    await nextTick()
    await initPlaybackRelatedServices()
    return
  }

  playbackService.init(plyrWrapper)
  isAudioContextSupported && audioService.init(playbackService.player.media)
}

watch(preferenceStore.initialized, async initialized => {
  if (!initialized) return
  await initPlaybackRelatedServices()
}, { immediate: true })

const setupControlHidingTimer = () => {
  hideControlsTimeout = window.setTimeout(() => root.value?.classList.add('hide-controls'), 5000)
}

const showControls = throttle(() => {
  if (!document.fullscreenElement) return

  root.value?.classList.remove('hide-controls')
  window.clearTimeout(hideControlsTimeout)
  setupControlHidingTimer()
}, 100)

const { isFullscreen, toggle: toggleFullscreen } = useFullscreen(root)

watch(isFullscreen, fullscreen => {
  if (fullscreen) {
    setupControlHidingTimer()
    root.value?.classList.remove('hide-controls')
  } else {
    window.clearTimeout(hideControlsTimeout)
  }
})

eventBus.on('FULLSCREEN_TOGGLE', () => toggleFullscreen())
</script>

<style lang="scss" scoped>
footer {
  background-color: var(--color-bg-secondary);
  background-size: 0;
  height: var(--footer-height);
  display: flex;
  box-shadow: 0 0 30px 20px rgba(0, 0, 0, .2);
  flex-direction: column;
  position: relative;
  z-index: 3;

  .wrapper {
    position: relative;
    display: flex;
    flex: 1;
  }

  .fullscreen-backdrop {
    display: none;
  }

  &:fullscreen {
    padding: calc(100vh - 9rem) 5vw 0;
    background: none;

    &.hide-controls :not(.fullscreen-backdrop) {
      transition: opacity 2s ease-in-out;
      opacity: 0;
    }

    .wrapper {
      z-index: 3;
    }

    &::before {
      background-color: #000;
      background-image: linear-gradient(135deg, #111 25%, transparent 25%),
      linear-gradient(225deg, #111 25%, transparent 25%),
      linear-gradient(45deg, #111 25%, transparent 25%),
      linear-gradient(315deg, #111 25%, rgba(255, 255, 255, 0) 25%);
      background-position: 6px 0, 6px 0, 0 0, 0 0;
      background-size: 6px 6px;
      background-repeat: repeat;
      content: '';
      position: absolute;
      width: calc(100% + 40rem);
      height: calc(100% + 40rem);
      top: 0;
      left: 0;
      opacity: .5;
      z-index: 1;
      pointer-events: none;
      margin: -20rem;
      transform: rotate(10deg);
    }

    &::after {
      background-image: linear-gradient(0deg, rgba(0, 0, 0, 1) 0%, rgba(255, 255, 255, 0) 30vh);
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      z-index: 1;
      pointer-events: none;
    }

    .fullscreen-backdrop {
      filter: saturate(.2);
      display: block;
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      background-size: cover;
      background-repeat: no-repeat;
      background-position: top center;
    }
  }
}
</style>

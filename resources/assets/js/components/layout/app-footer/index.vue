<template>
  <footer
    ref="root"
    class="flex flex-col relative z-20 bg-k-bg-secondary h-k-footer-height"
    @mousemove="showControls"
    @contextmenu.prevent="requestContextMenu"
  >
    <AudioPlayer v-show="song" />

    <div class="fullscreen-backdrop hidden" />

    <div class="wrapper relative flex flex-1">
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

const backgroundBackgroundImage = computed(() => {
  const src = artist.value?.image ?? song.value?.album_cover
  return src ? `url(${src})` : 'none'
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
    // setupControlHidingTimer()
    root.value?.classList.remove('hide-controls')
  } else {
    window.clearTimeout(hideControlsTimeout)
  }
})

eventBus.on('FULLSCREEN_TOGGLE', () => toggleFullscreen())
</script>

<style lang="postcss" scoped>
footer {
  box-shadow: 0 0 30px 20px rgba(0, 0, 0, .2);

  .fullscreen-backdrop {
    background-image: v-bind(backgroundBackgroundImage);
  }

  &:fullscreen {
    padding: calc(100vh - 9rem) 5vw 0;
    @apply bg-none;

    &.hide-controls :not(.fullscreen-backdrop) {
      transition: opacity 2s ease-in-out !important; /* overriding all children's custom transition, if any */
      @apply opacity-0;
    }

    .wrapper {
      @apply z-[3]
    }

    &::before {
      @apply bg-black bg-repeat absolute top-0 left-0 opacity-50 z-[1] pointer-events-none -m-[20rem];
      content: '';
      background-image: linear-gradient(135deg, #111 25%, transparent 25%),
      linear-gradient(225deg, #111 25%, transparent 25%),
      linear-gradient(45deg, #111 25%, transparent 25%),
      linear-gradient(315deg, #111 25%, rgba(255, 255, 255, 0) 25%);
      background-position: 6px 0, 6px 0, 0 0, 0 0;
      background-size: 6px 6px;
      width: calc(100% + 40rem);
      height: calc(100% + 40rem);
      transform: rotate(10deg);
    }

    &::after {
      background-image: linear-gradient(0deg, rgba(0, 0, 0, 1) 0%, rgba(255, 255, 255, 0) 30vh);
      content: '';
      @apply absolute w-full h-full top-0 left-0 z-[1] pointer-events-none;
    }

    .fullscreen-backdrop {
      @apply saturate-[0.2] block absolute top-0 left-0 w-full h-full z-0 bg-cover bg-no-repeat bg-top;
    }
  }
}
</style>

<template>
  <footer
    ref="root"
    class="flex flex-col relative z-20 bg-k-bg-secondary h-k-footer-height"
    @mousemove="showControls"
    @contextmenu.prevent="requestContextMenu"
  >
    <AudioPlayer v-show="currentStreamable" :class="isRadio && 'pointer-events-none'" />

    <div class="fullscreen-backdrop hidden" />

    <div class="wrapper relative flex flex-1">
      <RadioStationInfo v-if="isRadio" />
      <SongInfo v-else />
      <PlaybackControls />
      <ExtraControls />
    </div>

    <Transition>
      <UpNext v-show="showingUpNext" :playable="nextPlayable" class="up-next" />
    </Transition>
  </footer>
</template>

<script lang="ts" setup>
import { throttle } from 'lodash'
import { computed, nextTick, ref, watch } from 'vue'
import { useFullscreen } from '@vueuse/core'
import { eventBus } from '@/utils/eventBus'
import { isEpisode, isRadioStation, isSong } from '@/utils/typeGuards'
import { isAudioContextSupported } from '@/utils/supports'
import { defineAsyncComponent, requireInjection } from '@/utils/helpers'
import { CurrentStreamableKey } from '@/symbols'
import { artistStore } from '@/stores/artistStore'
import { preferenceStore } from '@/stores/preferenceStore'
import { audioService } from '@/services/audioService'
import { playback } from '@/services/playbackManager'
import { useContextMenu } from '@/composables/useContextMenu'

import AudioPlayer from '@/components/layout/app-footer/AudioPlayer.vue'
import ExtraControls from '@/components/layout/app-footer/FooterExtraControls.vue'
import PlaybackControls from '@/components/layout/app-footer/FooterPlaybackControls.vue'

const SongInfo = defineAsyncComponent(() => import('@/components/layout/app-footer/FooterPlayableInfo.vue'))
const RadioStationInfo = defineAsyncComponent(() => import('@/components/layout/app-footer/FooterRadioStationInfo.vue'))
const UpNext = defineAsyncComponent(() => import('@/components/layout/app-footer/UpNext.vue'))
const PlayableContextMenu = defineAsyncComponent(() => import('@/components/playable/PlayableContextMenu.vue'))
const RadioStationContextMenu = defineAsyncComponent(() => import('@/components/radio/RadioStationContextMenu.vue'))

const currentStreamable = requireInjection(CurrentStreamableKey, ref())
let hideControlsTimeout: number

const root = ref<HTMLElement>()
const artist = ref<Artist>()
const nextPlayable = ref<Playable | null>(null)

const { isFullscreen, toggle: toggleFullscreen } = useFullscreen(root)
const { openContextMenu } = useContextMenu()

const showingUpNext = computed(() => nextPlayable.value && isFullscreen.value)
const isRadio = computed(() => currentStreamable.value && isRadioStation(currentStreamable.value))

const requestContextMenu = (event: MouseEvent) => {
  if (document.fullscreenElement || !currentStreamable.value) {
    return
  }

  if (isRadio.value) {
    openContextMenu<'RADIO_STATION'>(RadioStationContextMenu, event, {
      station: currentStreamable.value as RadioStation,
    })
  } else {
    openContextMenu<'PLAYABLES'>(PlayableContextMenu, event, {
      playables: [currentStreamable.value as Playable],
    })
  }
}

watch(currentStreamable, async streamable => {
  if (!streamable) {
    return
  }

  if (isSong(streamable)) {
    artist.value = await artistStore.resolve(streamable.artist_id)
  }
})

const appBackgroundImage = computed(() => {
  if (!currentStreamable.value) {
    return 'none'
  }

  let src: string | null = null

  if (isSong(currentStreamable.value)) {
    src = artist.value?.image ?? currentStreamable.value.album_cover
  } else if (isEpisode(currentStreamable.value)) {
    src = currentStreamable.value.episode_image
  } else if (isRadio.value) {
    src = (currentStreamable.value as RadioStation).logo
  }

  return src ? `url(${src})` : 'none'
})

const initPlaybackRelatedServices = async () => {
  const plyrWrapper = document.querySelector<HTMLElement>('.plyr')

  if (!plyrWrapper) {
    await nextTick()
    await initPlaybackRelatedServices()
    return
  }

  // Defaults to the queue playback over radio playback.
  const playbackService = playback()

  // If audio context is supported, initialize the audio service which handles audio processing (equalizer, etc.)
  if (isAudioContextSupported) {
    audioService.init(playbackService.player.media)
  }
}

watch(preferenceStore.initialized, async initialized => {
  if (!initialized) {
    return
  }

  await initPlaybackRelatedServices()
}, { immediate: true })

const setupControlHidingTimer = () => {
  hideControlsTimeout = window.setTimeout(() => root.value?.classList.add('hide-controls'), 5000)
}

const showControls = throttle(() => {
  if (!document.fullscreenElement) {
    return
  }

  root.value?.classList.remove('hide-controls')
  window.clearTimeout(hideControlsTimeout)
  setupControlHidingTimer()
}, 100)

watch(isFullscreen, fullscreen => {
  if (fullscreen) {
    setupControlHidingTimer()
    root.value?.classList.remove('hide-controls')
  } else {
    window.clearTimeout(hideControlsTimeout)
  }
})

eventBus.on('FULLSCREEN_TOGGLE', () => toggleFullscreen())
  .on('UP_NEXT', next => (nextPlayable.value = next))
</script>

<style lang="postcss" scoped>
.v-enter-active,
.v-leave-active {
  transition: opacity 2s ease;
}

.v-enter-from,
.v-leave-to {
  opacity: 0;
}

footer {
  box-shadow: 0 0 30px 20px rgba(0, 0, 0, 0.2);

  .fullscreen-backdrop {
    background-color: #1d1d1d;
    background-image: v-bind(appBackgroundImage);
  }

  &:fullscreen {
    padding: calc(100vh - 9rem) 5vw 0;
    @apply bg-none;

    &.hide-controls :not(.fullscreen-backdrop, .up-next, .up-next *) {
      transition: opacity 2s ease-in-out !important; /* overriding all children's custom transition, if any */
      @apply opacity-0;
    }

    .wrapper {
      @apply z-[3];
    }

    &::before {
      @apply bg-black bg-repeat absolute top-0 left-0 opacity-50 z-[1] pointer-events-none -m-[20rem];
      content: '';
      background-image:
        linear-gradient(135deg, #111 25%, transparent 25%), linear-gradient(225deg, #111 25%, transparent 25%),
        linear-gradient(45deg, #111 25%, transparent 25%), linear-gradient(315deg, #111 25%, rgba(255, 255, 255, 0) 25%);
      background-position:
        6px 0,
        6px 0,
        0 0,
        0 0;
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

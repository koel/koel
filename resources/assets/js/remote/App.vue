<template>
  <div id="wrapper" :class="{ 'standalone' : inStandaloneMode }">
    <template v-if="authenticated">
      <AlbumArtOverlay v-if="showAlbumArtOverlay && song" :album="song.album_id" />

      <main>
        <template v-if="connected">
          <div v-if="song" class="details">
            <div :style="{ backgroundImage: `url(${song.album_cover || defaultCover})` }" class="cover" />
            <div class="info">
              <div class="wrap">
                <p class="title text">{{ song.title }}</p>
                <p class="artist text">{{ song.artist_name }}</p>
                <p class="album text">{{ song.album_name }}</p>
              </div>
            </div>
          </div>
          <p v-else class="none text-secondary">No song is playing.</p>
          <footer>
            <a class="favorite" :class="song?.liked ? 'yep' : ''" @click.prevent="toggleFavorite">
              <Icon :icon="song?.liked ? faHeart : faEmptyHeart" />
            </a>
            <a class="prev" @click="playPrev">
              <Icon :icon="faStepBackward" />
            </a>
            <a class="play-pause" @click.prevent="togglePlayback">
              <Icon :icon="playing ? faPause : faPlay" />
            </a>
            <a class="next" @click.prevent="playNext">
              <Icon :icon="faStepForward" />
            </a>
            <span class="volume">
              <span
                v-show="showingVolumeSlider"
                id="volumeSlider"
                ref="volumeSlider"
                v-koel-clickaway="closeVolumeSlider"
              />
              <span class="icon" @click.stop="toggleVolumeSlider">
                <Icon :icon="muted ? faVolumeMute : faVolumeHigh" fixed-width />
              </span>
            </span>
          </footer>
        </template>
        <div v-else class="loader">
          <div v-if="!maxRetriesReached">
            <p>Searching for Koel…</p>
            <div class="signal" />
          </div>
          <p v-else>
            No active Koel instance found.
            <a class="rescan text-highlight" @click.prevent="rescan">Rescan</a>
          </p>
        </div>
      </main>
    </template>

    <div v-else class="login-wrapper vertical-center">
      <LoginForm @loggedin="onUserLoggedIn" />
    </div>
  </div>
</template>

<script lang="ts" setup>
import {
  faHeart,
  faPause,
  faPlay,
  faStepBackward,
  faStepForward,
  faVolumeHigh,
  faVolumeMute
} from '@fortawesome/free-solid-svg-icons'

import { faHeart as faEmptyHeart } from '@fortawesome/free-regular-svg-icons'
import noUISlider from 'nouislider'
import { authService, socketService } from '@/services'
import { preferenceStore, userStore } from '@/stores'
import { computed, defineAsyncComponent, nextTick, onMounted, ref, toRef, watch } from 'vue'
import { defaultCover, logger } from '@/utils'

const MAX_RETRIES = 10
const DEFAULT_VOLUME = 7

const AlbumArtOverlay = defineAsyncComponent(() => import('@/components/ui/AlbumArtOverlay.vue'))
const LoginForm = defineAsyncComponent(() => import('@/components/auth/LoginForm.vue'))

const volumeSlider = ref<EqualizerBandElement>()
const authenticated = ref(false)
const song = ref<Song>()
const connected = ref(false)
const muted = ref(false)
const showingVolumeSlider = ref(false)
const retries = ref(0)
const showAlbumArtOverlay = toRef(preferenceStore.state, 'show_album_art_overlay')
const volume = ref(DEFAULT_VOLUME)

const inStandaloneMode = ref(
  (window.navigator as any).standalone || window.matchMedia('(display-mode: standalone)').matches
)

watch(connected, async () => {
  await nextTick()

  if (!volumeSlider.value) return

  noUISlider.create(volumeSlider.value, {
    orientation: 'vertical',
    connect: [true, false],
    start: volume.value || DEFAULT_VOLUME,
    range: { min: 0, max: 10 },
    direction: 'rtl'
  })

  if (!volumeSlider.value.noUiSlider) {
    throw new Error('Failed to initialize noUISlider on element #volumeSlider')
  }

  volumeSlider.value.noUiSlider.on('change', (values: string[], handle: number) => {
    const volume = values[handle]
    muted.value = !volume
    socketService.broadcast('SOCKET_SET_VOLUME', volume)
  })
})

watch(volume, () => volumeSlider.value?.noUiSlider!.set(volume.value ?? DEFAULT_VOLUME))

const onUserLoggedIn = async () => {
  authenticated.value = true
  await init()
}

const init = async () => {
  try {
    userStore.init(await authService.getProfile())

    await socketService.init()

    socketService
      .listen('SOCKET_SONG', _song => (song.value = _song))
      .listen('SOCKET_PLAYBACK_STOPPED', () => song.value && (song.value.playback_state = 'Stopped'))
      .listen('SOCKET_VOLUME_CHANGED', (volume: number) => volumeSlider.value?.noUiSlider?.set(volume))
      .listen('SOCKET_STATUS', (data: { song?: Song, volume: number }) => {
        song.value = data.song
        volume.value = data.volume || DEFAULT_VOLUME
        connected.value = true
      })

    scan()
  } catch (e) {
    logger.error(e)
    authenticated.value = false
  }
}

const toggleVolumeSlider = () => (showingVolumeSlider.value = !showingVolumeSlider.value)
const closeVolumeSlider = () => (showingVolumeSlider.value = false)

const toggleFavorite = () => {
  if (!song.value) {
    return
  }

  song.value.liked = !song.value.liked
  socketService.broadcast('SOCKET_TOGGLE_FAVORITE')
}

const togglePlayback = () => {
  if (song.value) {
    song.value.playback_state = song.value.playback_state === 'Playing' ? 'Paused' : 'Playing'
  }

  socketService.broadcast('SOCKET_TOGGLE_PLAYBACK')
}

const playNext = () => socketService.broadcast('SOCKET_PLAY_NEXT')
const playPrev = () => socketService.broadcast('SOCKET_PLAY_PREV')
const getStatus = () => socketService.broadcast('SOCKET_GET_STATUS')

const scan = () => {
  if (!connected.value) {
    if (!maxRetriesReached.value) {
      getStatus()
      retries.value++
      window.setTimeout(scan, 1000)
    }
  } else {
    retries.value = 0
  }
}

const rescan = () => {
  retries.value = 0
  scan()
}

const playing = computed(() => Boolean(song.value?.playback_state === 'Playing'))
const maxRetriesReached = computed(() => retries.value >= MAX_RETRIES)

onMounted(async () => {
  // The app has just been initialized, check if we can get the user data with an already existing token
  if (authService.hasApiToken()) {
    authenticated.value = true
    await init()
  }
})
</script>

<style lang="postcss">
body, html {
  height: 100vh;
  position: relative;
}

#wrapper {
  height: 100vh;
  background: var(--color-bg-primary);

  .login-wrapper {
    display: flex;
    min-height: 100vh;
    flex-direction: column;
  }

  .loader {
    display: flex;
    justify-content: center;
    align-items: center;
    flex: 1;
    position: relative;

    p {
      position: absolute;
      height: 100%;
      width: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      top: 0;
      left: 0;
      padding-bottom: 40px;
    }

    .signal {
      border: 1px solid var(--color-highlight);
      border-radius: 50%;
      height: 0;
      opacity: 0;
      width: 50vw;
      animation: pulsate 1.5s ease-out;
      animation-iteration-count: infinite;
      transform: translate(-50%, -50%);
    }

    .rescan {
      margin-left: 5px;
    }

    @keyframes pulsate {
      0% {
        transform: scale(.1);
        opacity: 0.0;
      }
      50% {
        opacity: 1;
      }
      100% {
        transform: scale(1.2);
        opacity: 0;
      }
    }
  }
}

main {
  height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  text-align: center;
  z-index: 1;
  position: relative;

  .none, .details {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: space-around;
  }

  .details {
    .info {
      width: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-around;
    }

    .cover {
      margin: 0 auto;
      width: calc(70vw + 4px);
      height: calc(70vw + 4px);
      border-radius: 50%;
      border: 2px solid var(--color-text-primary);
      background-position: center center;
      background-size: cover;
      background-color: var(--color-bg-secondary);
    }

    .text {
      max-width: 90%;
      margin: 0 auto;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      line-height: 1.3;
    }

    .title {
      font-size: 6vmin;
      font-weight: bold;
      margin: 0 auto 10px;
    }

    .artist {
      font-size: 5vmin;
      margin: 0 auto 6px;
      opacity: .5;
    }

    .album {
      font-size: 4vmin;
      opacity: .5;
    }
  }

  footer {
    height: 18vh;
    display: flex;
    justify-content: space-around;
    align-items: center;
    border-top: 1px solid rgba(255, 255, 255, .1);
    padding: 1rem 0;
    font-size: 5vmin;

    a {
      color: var(--color-text-primary);

      &:active {
        opacity: .8;
      }
    }

    .favorite.yep {
      color: var(--color-maroon);
    }

    .prev, .next {
      font-size: 6vmin;
    }

    .play-pause {
      width: 16vmin;
      height: 16vmin;
      border: 1px solid var(--color-text-primary);
      border-radius: 50%;
      font-size: 7vmin;
      display: flex;
      place-content: center;
      place-items: center;

      .fa-play {
        margin-left: 4px;
      }
    }
  }
}

#wrapper.standalone {
  padding-top: 20px;

  main {
    .details {
      .cover {
        width: calc(80vw - 4px);
        height: calc(80vw - 4px);
      }
    }

    .footer {
      height: 20vh;
    }
  }
}

.volume {
  position: relative;

  .icon {
    width: 20px;
    display: inline-block;
    text-align: center;
  }
}

#volumeSlider {
  height: 80px;
  position: absolute;
  bottom: calc(50% + 26px);
}

.noUi-target {
  background: var(--color-text-primary);
  border-radius: 4px;
  border: 0;
  box-shadow: none;
  left: 7px;
}

.noUi-base {
  height: calc(100% - 16px);
  border-radius: 4px;
}

.noUi-vertical {
  width: 8px;
}

.noUi-vertical .noUi-handle {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 0;
  left: -12px;
  top: 0;
  background: var(--color-highlight);
  box-shadow: none;

  &::after, &::before {
    display: none;
  }
}

.noUi-connect {
  background: transparent;
  box-shadow: none;
}
</style>

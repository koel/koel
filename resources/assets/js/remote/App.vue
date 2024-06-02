<template>
  <div :class="{ 'standalone' : inStandaloneMode }" class="h-screen bg-k-bg-primary">
    <template v-if="authenticated">
      <AlbumArtOverlay v-if="showAlbumArtOverlay && state.song && isSong(state.song)" :album="state.song.album_id" />

      <main class="h-screen flex flex-col items-center justify-between text-center relative z-[1]">
        <template v-if="connected">
          <template v-if="state.song">
            <SongDetails :song="state.song" />
            <RemoteFooter :song="state.song" />
          </template>

          <p v-else class="text-k-text-secondary">No song is playing.</p>
        </template>
        <Scanner v-else />
      </main>
    </template>

    <div v-else class="h-screen flex flex-col items-center justify-center">
      <LoginForm @loggedin="onUserLoggedIn" />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { authService, socketService } from '@/services'
import { preferenceStore, userStore } from '@/stores'
import { defineAsyncComponent, onMounted, provide, reactive, ref, toRef } from 'vue'
import { isSong, logger } from '@/utils'
import { RemoteState } from '@/remote/types'

const SongDetails = defineAsyncComponent(() => import('@/remote/components/SongDetails.vue'))
const Scanner = defineAsyncComponent(() => import('@/remote/components/Scanner.vue'))
const RemoteFooter = defineAsyncComponent(() => import('@/remote/components/RemoteFooter.vue'))
const AlbumArtOverlay = defineAsyncComponent(() => import('@/components/ui/AlbumArtOverlay.vue'))
const LoginForm = defineAsyncComponent(() => import('@/components/auth/LoginForm.vue'))

const authenticated = ref(false)
const connected = ref(false)
const showAlbumArtOverlay = toRef(preferenceStore.state, 'show_album_art_overlay')

const inStandaloneMode = ref(
  (window.navigator as any).standalone || window.matchMedia('(display-mode: standalone)').matches
)

const onUserLoggedIn = async () => {
  authenticated.value = true
  await init()
}

const state = reactive<RemoteState>({
  volume: 0,
  song: null as Playable | null
})

provide('state', state)

const init = async () => {
  try {
    userStore.init(await authService.getProfile())
    await socketService.init()

    socketService
      .listen('SOCKET_SONG', song => (state.song = song))
      .listen('SOCKET_PLAYBACK_STOPPED', () => state.song && (state.song.playback_state = 'Stopped'))
      .listen('SOCKET_VOLUME_CHANGED', (volume: number) => state.volume = volume)
      .listen('SOCKET_STATUS', (data: { song?: Playable, volume: number }) => {
        state.volume = data.volume || 0
        state.song = data.song || null
        connected.value = true
      })
  } catch (error: unknown) {
    logger.error(error)
    authenticated.value = false
  }
}

onMounted(async () => {
  // The app has just been initialized, check if we can get the user data with an already existing token
  if (authService.hasApiToken()) {
    authenticated.value = true
    await init()
  }
})
</script>

<style lang="postcss" scoped>
.standalone {
  @apply pt-[20px];

  :deep(.cover) {
    width: calc(80vw - 4px);
    height: calc(80vw - 4px);
  }
}
</style>

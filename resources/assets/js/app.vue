<template>
  <div id="main" v-if="authenticated">
    <Hotkeys/>
    <EventListeners/>
    <AppHeader/>
    <MainWrapper/>
    <AppFooter/>
    <SupportKoel/>
    <Overlay ref="overlay"/>
  </div>

  <template v-else>
    <div class="login-wrapper">
      <LoginForm @loggedin="onUserLoggedIn"/>
    </div>
  </template>

  <SongContextMenu :songs="contextMenuSongs" ref="songContextMenu"/>
  <AlbumContextMenu :album="contextMenuAlbum" ref="albumContextMenu"/>
  <ArtistContextMenu :artist="contextMenuArtist" ref="artistContextMenu"/>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, nextTick, onMounted, ref } from 'vue'

import AppHeader from '@/components/layout/app-header.vue'
import AppFooter from '@/components/layout/app-footer/index.vue'
import EventListeners from '@/components/utils/event-listeners.vue'
import Hotkeys from '@/components/utils/hotkeys.vue'
import LoginForm from '@/components/auth/login-form.vue'
import MainWrapper from '@/components/layout/main-wrapper/index.vue'
import Overlay from '@/components/ui/overlay.vue'

import { $, eventBus, hideOverlay, showOverlay, arrayify } from '@/utils'
import { favoriteStore, preferenceStore as preferences, queueStore, sharedStore } from '@/stores'
import { auth, playback, socket } from '@/services'

const SongContextMenu = defineAsyncComponent(() => import('@/components/song/context-menu.vue'))
const AlbumContextMenu = defineAsyncComponent(() => import('@/components/album/context-menu.vue'))
const ArtistContextMenu = defineAsyncComponent(() => import('@/components/artist/context-menu.vue'))
const SupportKoel = defineAsyncComponent(() => import('@/components/meta/support-koel.vue'))

const authenticated = ref(false)
const contextMenuSongs = ref<Song[]>([])
const contextMenuAlbum = ref<Album>()
const contextMenuArtist = ref<Artist>()

const overlay = ref<HTMLElement>()
const songContextMenu = ref<InstanceType<typeof SongContextMenu>>()
const albumContextMenu = ref<InstanceType<typeof AlbumContextMenu>>()
const artistContextMenu = ref<InstanceType<typeof ArtistContextMenu>>()

/**
 * Request for notification permission if it's not provided and the user is OK with notifications.
 */
const requestNotificationPermission = async () => {
  if (window.Notification && preferences.notify && window.Notification.permission !== 'granted') {
    preferences.notify = await window.Notification.requestPermission() === 'denied'
  }
}

const onUserLoggedIn = () => {
  authenticated.value = true
  init()
}

const subscribeToBroadcastEvents = () => {
  socket.listen('SOCKET_TOGGLE_FAVORITE', (): void => {
    if (queueStore.current) {
      favoriteStore.toggleOne(queueStore.current)
    }
  })
}

onMounted(async () => {
  // The app has just been initialized, check if we can get the user data with an already existing token
  if (auth.hasToken()) {
    authenticated.value = true
    await init()
  }

  // Add an ugly mac/non-mac class for OS-targeting styles.
  // I'm crying inside.
  $.addClass(document.documentElement, navigator.userAgent.includes('Mac') ? 'mac' : 'non-mac')
})

eventBus.on('SONG_CONTEXT_MENU_REQUESTED', async (e: MouseEvent, songs: Song | Song[]) => {
  contextMenuSongs.value = arrayify(songs)
  await nextTick()
  songContextMenu.value?.open(e.pageY, e.pageX)
})

eventBus.on('ALBUM_CONTEXT_MENU_REQUESTED', async (e: MouseEvent, album: Album) => {
  contextMenuAlbum.value = album
  await nextTick()
  albumContextMenu.value?.open(e.pageY, e.pageX)
})

eventBus.on('ARTIST_CONTEXT_MENU_REQUESTED', async (e: MouseEvent, artist: Artist) => {
  contextMenuArtist.value = artist
  await nextTick()
  artistContextMenu.value?.open(e.pageY, e.pageX)
})

const init = async () => {
  showOverlay()
  await socket.init()

  // Make the most important HTTP request to get all necessary data from the server.
  // Afterwards, init all mandatory stores and services.
  try {
    await sharedStore.init()

    window.setTimeout(() => {
      playback.init()
      hideOverlay()
      requestNotificationPermission()

      window.addEventListener('beforeunload', (e: BeforeUnloadEvent): void => {
        if (!preferences.confirmClosing) {
          return
        }

        e.preventDefault()
        e.returnValue = ''
      })

      subscribeToBroadcastEvents()

      // Let all other components know we're ready.
      eventBus.emit('KOEL_READY')
    }, 100)
  } catch (err) {
    authenticated.value = false
    throw err
  }
}
</script>

<style lang="scss">
@import "#/app.scss";

#dragGhost {
  display: inline-block;
  background: var(--color-green);
  padding: .8rem;
  border-radius: .2rem;
  color: var(--color-text-primary);
  font-family: var(--font-family);
  font-size: 1rem;
  font-weight: var(--font-weight-light);
  position: fixed;
  top: 0;
  left: 0;
  z-index: -1;

  @media (hover: none) {
    display: none;
  }
}

#copyArea {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;
  bottom: 1px;

  @media (hover: none) {
    display: none;
  }
}

#main, .login-wrapper {
  display: flex;
  height: 100vh;
  flex-direction: column;
}

.login-wrapper {
  @include vertical-center();
  user-select: none;
  padding-bottom: 0;
}
</style>

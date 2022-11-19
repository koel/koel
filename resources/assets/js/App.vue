<template>
  <Overlay ref="overlay"/>
  <DialogBox ref="dialog"/>
  <MessageToaster ref="toaster"/>
  <GlobalEventListeners/>
  <OfflineNotification v-if="offline"/>

  <div v-if="authenticated" id="main" @dragend="onDragEnd" @dragover="onDragOver" @drop="onDrop">
    <Hotkeys/>
    <MainWrapper/>
    <AppFooter/>
    <SupportKoel/>
    <SongContextMenu/>
    <AlbumContextMenu/>
    <ArtistContextMenu/>
    <PlaylistContextMenu/>
    <PlaylistFolderContextMenu/>
    <CreateNewPlaylistContextMenu/>
    <DropZone v-show="showDropZone"/>
  </div>

  <div v-else class="login-wrapper">
    <LoginForm @loggedin="onUserLoggedIn"/>
  </div>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, nextTick, onMounted, provide, ref, watch } from 'vue'
import { commonStore, preferenceStore as preferences, queueStore } from '@/stores'
import { authService, socketListener, socketService, uploadService } from '@/services'
import { CurrentSongKey, DialogBoxKey, MessageToasterKey, OverlayKey } from '@/symbols'
import { useNetworkStatus, useRouter } from '@/composables'

import DialogBox from '@/components/ui/DialogBox.vue'
import MessageToaster from '@/components/ui/MessageToaster.vue'
import Overlay from '@/components/ui/Overlay.vue'
import OfflineNotification from '@/components/ui/OfflineNotification.vue'

// Do not dynamic-import app footer, as it contains the <audio> element
// that is necessary to properly initialize the playService and equalizer.
import AppFooter from '@/components/layout/app-footer/index.vue'

// GlobalEventListener must NOT be lazy-loaded, so that it can handle LOG_OUT event properly.
import { GlobalEventListeners } from '@/components/utils/GlobalEventListeners'

const Hotkeys = defineAsyncComponent(() => import('@/components/utils/HotkeyListener.vue'))
const LoginForm = defineAsyncComponent(() => import('@/components/auth/LoginForm.vue'))
const MainWrapper = defineAsyncComponent(() => import('@/components/layout/main-wrapper/index.vue'))
const AlbumContextMenu = defineAsyncComponent(() => import('@/components/album/AlbumContextMenu.vue'))
const ArtistContextMenu = defineAsyncComponent(() => import('@/components/artist/ArtistContextMenu.vue'))
const PlaylistContextMenu = defineAsyncComponent(() => import('@/components/playlist/PlaylistContextMenu.vue'))
const PlaylistFolderContextMenu = defineAsyncComponent(() => import('@/components/playlist/PlaylistFolderContextMenu.vue'))
const SongContextMenu = defineAsyncComponent(() => import('@/components/song/SongContextMenu.vue'))
const CreateNewPlaylistContextMenu = defineAsyncComponent(() => import('@/components/playlist/CreateNewPlaylistContextMenu.vue'))
const SupportKoel = defineAsyncComponent(() => import('@/components/meta/SupportKoel.vue'))
const DropZone = defineAsyncComponent(() => import('@/components/ui/upload/DropZone.vue'))

const overlay = ref<InstanceType<typeof Overlay>>()
const dialog = ref<InstanceType<typeof DialogBox>>()
const toaster = ref<InstanceType<typeof MessageToaster>>()
const currentSong = ref<Song | null>(null)
const authenticated = ref(false)
const showDropZone = ref(false)

const { isCurrentScreen } = useRouter()
const { offline } = useNetworkStatus()

/**
 * Request for notification permission if it's not provided and the user is OK with notifications.
 */
const requestNotificationPermission = async () => {
  if (preferences.notify && window.Notification && window.Notification.permission !== 'granted') {
    preferences.notify = await window.Notification.requestPermission() === 'denied'
  }
}

const onUserLoggedIn = async () => {
  authenticated.value = true
  await init()
}

onMounted(async () => {
  // The app has just been initialized, check if we can get the user data with an already existing token
  if (authService.hasApiToken()) {
    authenticated.value = true
    await init()
  }

  // Add an ugly mac/non-mac class for OS-targeting styles.
  // I'm crying inside.
  document.documentElement.classList.add(navigator.userAgent.includes('Mac') ? 'mac' : 'non-mac')
})

const init = async () => {
  overlay.value.show()

  try {
    await commonStore.init()
    await nextTick()

    await requestNotificationPermission()

    window.addEventListener('beforeunload', (e: BeforeUnloadEvent) => {
      if (uploadService.shouldWarnUponWindowUnload() || preferences.confirmClosing) {
        e.preventDefault()
        e.returnValue = ''
      }
    })

    await socketService.init() && socketListener.listen()
    overlay.value.hide()
  } catch (err) {
    authenticated.value = false
    throw err
  }
}

const onDragOver = (e: DragEvent) => {
  showDropZone.value = Boolean(e.dataTransfer?.types.includes('Files')) && !isCurrentScreen('Upload')
}

watch(() => queueStore.current, song => song && (currentSong.value = song))

const onDragEnd = () => (showDropZone.value = false)
const onDrop = () => (showDropZone.value = false)

provide(OverlayKey, overlay)
provide(DialogBoxKey, dialog)
provide(MessageToasterKey, toaster)
provide(CurrentSongKey, currentSong)
</script>

<style lang="scss">
@import "#/app.scss";

#dragGhost {
  display: inline-block;
  background: var(--color-green);
  padding: .8rem;
  border-radius: .3rem;
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
  justify-content: flex-end;
}

#main {
  @media screen and (max-width: 768px) {
    position: absolute;
    height: 100%;
    width: 100%;
    top: 0;
    padding-top: var(--header-height);
  }
}

.login-wrapper {
  @include vertical-center();
  user-select: none;
  padding-bottom: 0;
}
</style>

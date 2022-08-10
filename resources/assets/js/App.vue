<template>
  <Overlay/>

  <div id="main" v-if="authenticated">
    <Hotkeys/>
    <EventListeners/>
    <AppHeader/>
    <MainWrapper/>
    <AppFooter/>
    <SupportKoel/>
  </div>

  <template v-else>
    <div class="login-wrapper">
      <LoginForm @loggedin="onUserLoggedIn"/>
    </div>
  </template>

  <SongContextMenu/>
  <AlbumContextMenu/>
  <ArtistContextMenu/>
  <PlaylistFolderContextMenu/>

  <DialogBox ref="dialog"/>
  <MessageToaster ref="toaster"/>
</template>

<script lang="ts" setup>
import { defineAsyncComponent, nextTick, onMounted, provide, ref } from 'vue'
import { eventBus, hideOverlay, showOverlay } from '@/utils'
import { commonStore, preferenceStore as preferences } from '@/stores'
import { authService, playbackService, socketListener, socketService, uploadService } from '@/services'
import { DialogBoxKey, MessageToasterKey } from '@/symbols'

import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/app-footer/index.vue'
import EventListeners from '@/components/utils/EventListeners.vue'
import Hotkeys from '@/components/utils/HotkeyListener.vue'
import LoginForm from '@/components/auth/LoginForm.vue'
import MainWrapper from '@/components/layout/main-wrapper/index.vue'
import Overlay from '@/components/ui/Overlay.vue'
import AlbumContextMenu from '@/components/album/AlbumContextMenu.vue'
import ArtistContextMenu from '@/components/artist/ArtistContextMenu.vue'
import PlaylistFolderContextMenu from '@/components/playlist/PlaylistFolderContextMenu.vue'
import SongContextMenu from '@/components/song/SongContextMenu.vue'
import DialogBox from '@/components/ui/DialogBox.vue'
import MessageToaster from '@/components/ui/MessageToaster.vue'

const SupportKoel = defineAsyncComponent(() => import('@/components/meta/SupportKoel.vue'))

const dialog = ref<InstanceType<typeof DialogBox>>()
const toaster = ref<InstanceType<typeof MessageToast>>()
const authenticated = ref(false)

/**
 * Request for notification permission if it's not provided and the user is OK with notifications.
 */
const requestNotificationPermission = async () => {
  if (preferences.notify && window.Notification && window.Notification.permission !== 'granted') {
    preferences.notify = await window.Notification.requestPermission() === 'denied'
  }
}

const onUserLoggedIn = () => {
  authenticated.value = true
  init()
}

onMounted(async () => {
  // The app has just been initialized, check if we can get the user data with an already existing token
  if (authService.hasToken()) {
    authenticated.value = true
    await init()
  }

  // Add an ugly mac/non-mac class for OS-targeting styles.
  // I'm crying inside.
  document.documentElement.classList.add(navigator.userAgent.includes('Mac') ? 'mac' : 'non-mac')
})

const init = async () => {
  showOverlay()

  try {
    await commonStore.init()
    await nextTick()

    playbackService.init()
    await requestNotificationPermission()

    window.addEventListener('beforeunload', (e: BeforeUnloadEvent) => {
      if (uploadService.shouldWarnUponWindowUnload() || preferences.confirmClosing) {
        e.preventDefault()
        e.returnValue = ''
      }
    })

    await socketService.init() && socketListener.listen()

    hideOverlay()

    // Let all other components know we're ready.
    eventBus.emit('KOEL_READY')
  } catch (err) {
    authenticated.value = false
    throw err
  }
}

provide(DialogBoxKey, dialog)
provide(MessageToasterKey, toaster)
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
}

.login-wrapper {
  @include vertical-center();
  user-select: none;
  padding-bottom: 0;
}
</style>

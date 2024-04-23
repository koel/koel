<template>
  <Overlay ref="overlay" />
  <DialogBox ref="dialog" />
  <MessageToaster ref="toaster" />
  <GlobalEventListeners />
  <OfflineNotification v-if="!online" />

  <main
    v-if="layout === 'main' && initialized"
    class="absolute md:relative top-0 h-full md:h-screen pt-k-header-height md:pt-0 w-full md:w-auto flex flex-col justify-end"
    @dragend="onDragEnd"
    @dragover="onDragOver"
    @drop="onDrop"
  >
    <Hotkeys />
    <MainWrapper />
    <AppFooter />
    <SupportKoel />
    <SongContextMenu />
    <AlbumContextMenu />
    <ArtistContextMenu />
    <PlaylistContextMenu />
    <PlaylistFolderContextMenu />
    <CreateNewPlaylistContextMenu />
    <DropZone v-show="showDropZone" @close="showDropZone = false" />
  </main>

  <LoginForm v-if="layout === 'auth'" @loggedin="onUserLoggedIn" />

  <AcceptInvitation v-if="layout === 'invitation'" />
  <ResetPasswordForm v-if="layout === 'reset-password'" />

  <AppInitializer v-if="authenticated" @error="onInitError" @success="onInitSuccess" />
</template>

<script lang="ts" setup>
import { defineAsyncComponent, onMounted, provide, ref, watch } from 'vue'
import { useOnline } from '@vueuse/core'
import { queueStore } from '@/stores'
import { authService } from '@/services'
import { CurrentSongKey, DialogBoxKey, MessageToasterKey, OverlayKey } from '@/symbols'
import { useRouter } from '@/composables'

import DialogBox from '@/components/ui/DialogBox.vue'
import MessageToaster from '@/components/ui/message-toaster/MessageToaster.vue'
import Overlay from '@/components/ui/Overlay.vue'
import OfflineNotification from '@/components/ui/OfflineNotification.vue'

// Do not dynamic-import app footer, as it contains the <audio> element
// that is necessary to properly initialize the playService and equalizer.
import AppFooter from '@/components/layout/app-footer/index.vue'

// GlobalEventListener must NOT be lazy-loaded, so that it can handle LOG_OUT event properly.
import GlobalEventListeners from '@/components/utils/GlobalEventListeners.vue'
import AppInitializer from '@/components/utils/AppInitializer.vue'

const Hotkeys = defineAsyncComponent(() => import('@/components/utils/HotkeyListener.vue'))
const LoginForm = defineAsyncComponent(() => import('@/components/auth/LoginForm.vue'))
const MainWrapper = defineAsyncComponent(() => import('@/components/layout/main-wrapper/index.vue'))
const AlbumContextMenu = defineAsyncComponent(() => import('@/components/album/AlbumContextMenu.vue'))
const ArtistContextMenu = defineAsyncComponent(() => import('@/components/artist/ArtistContextMenu.vue'))
const PlaylistContextMenu = defineAsyncComponent(() => import('@/components/playlist/PlaylistContextMenu.vue'))
const PlaylistFolderContextMenu = defineAsyncComponent(() => import('@/components/playlist/PlaylistFolderContextMenu.vue'))
const SongContextMenu = defineAsyncComponent(() => import('@/components/song/SongContextMenu.vue'))
const CreateNewPlaylistContextMenu = defineAsyncComponent(() => import('@/components/playlist/CreatePlaylistContextMenu.vue'))
const SupportKoel = defineAsyncComponent(() => import('@/components/meta/SupportKoel.vue'))
const DropZone = defineAsyncComponent(() => import('@/components/ui/upload/DropZone.vue'))
const AcceptInvitation = defineAsyncComponent(() => import('@/components/invitation/AcceptInvitation.vue'))
const ResetPasswordForm = defineAsyncComponent(() => import('@/components/auth/ResetPasswordForm.vue'))

const overlay = ref<InstanceType<typeof Overlay>>()
const dialog = ref<InstanceType<typeof DialogBox>>()
const toaster = ref<InstanceType<typeof MessageToaster>>()
const currentSong = ref<Song>()
const showDropZone = ref(false)

const layout = ref<'main' | 'auth' | 'invitation' | 'reset-password'>()

const { isCurrentScreen, getCurrentScreen, resolveRoute } = useRouter()
const online = useOnline()

const authenticated = ref(false)
const initialized = ref(false)

const triggerAppInitialization = () => (authenticated.value = true)

const onUserLoggedIn = () => {
  layout.value = 'main'
  triggerAppInitialization()
}

const onInitSuccess = async () => {
  authenticated.value = false
  initialized.value = true

  // call resolveRoute() after init() so that the onResolve hooks can use the stores
  await resolveRoute()
  layout.value = 'main'
}

const onInitError = () => {
  authenticated.value = false
  layout.value = 'auth'
}

onMounted(async () => {
  // If the user is authenticated via a proxy, we have the token in the window object.
  // Simply forward it to the authService and continue with the normal flow.
  if (window.AUTH_TOKEN) {
    authService.setTokensUsingCompositeToken(window.AUTH_TOKEN)
  }

  // The app has just been initialized, check if we can get the user data with an already existing token
  if (authService.hasApiToken()) {
    triggerAppInitialization()
    return
  }

  await resolveRoute()

  switch (getCurrentScreen()) {
    case 'Invitation.Accept':
      layout.value = 'invitation'
      break
    case 'Password.Reset':
      layout.value = 'reset-password'
      break
    default:
      layout.value = 'auth'
  }

  // Add an ugly mac/non-mac class for OS-targeting styles.
  // I'm crying inside.
  document.documentElement.classList.add(navigator.userAgent.includes('Mac') ? 'mac' : 'non-mac')
})

const onDragOver = (e: DragEvent) => {
  showDropZone.value = Boolean(e.dataTransfer?.types.includes('Files')) && !isCurrentScreen('Upload')
}

watch(() => queueStore.current, song => (currentSong.value = song))

const onDragEnd = () => (showDropZone.value = false)
const onDrop = () => (showDropZone.value = false)

provide(OverlayKey, overlay)
provide(DialogBoxKey, dialog)
provide(MessageToasterKey, toaster)
provide(CurrentSongKey, currentSong)
</script>

<style lang="postcss">
#dragGhost {
  @apply inline-block py-2 pl-8 pr-3 rounded-md text-base font-sans fixed top-0 left-0 z-[-1] bg-k-success
  text-k-text-primary no-hover:hidden;
}

#copyArea {
  @apply absolute -left-full bottom-px w-px h-px no-hover:hidden;
}
</style>

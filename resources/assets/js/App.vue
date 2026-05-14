<template>
  <Overlay ref="overlay" />
  <DialogBox ref="dialog" />
  <MessageToaster ref="toaster" />
  <GlobalEventListeners />
  <OfflineNotification v-if="!online" />

  <main
    v-if="layout === 'default' && initialized"
    class="absolute md:relative top-0 h-full md:h-screen pt-k-header-height md:pt-0 w-full md:w-auto flex flex-col justify-end"
    @dragend="onDragEnd"
    @dragleave="onDragLeave"
    @dragover="onDragOver"
    @drop="onDrop"
  >
    <HotkeyListener />
    <MainWrapper />
    <AppFooter />
    <SupportKoel />
    <AiAssistantScreen v-if="commonStore.state.uses_ai" v-show="isCurrentScreen('AI')" />
    <DropZone v-show="showDropZone" @close="showDropZone = false" />
  </main>

  <LoginForm v-if="layout === 'login'" @loggedin="triggerAppInitialization" />
  <Embed v-if="layout === 'embed'" />

  <AcceptInvitation v-if="layout === 'invitation'" />
  <ResetPasswordForm v-if="layout === 'reset-password'" />

  <AppInitializer v-if="authenticated" @error="onInitError" @success="onInitSuccess" />

  <ContextMenu />
</template>

<script lang="ts" setup>
import { defineAsyncComponent } from '@/utils/helpers'
import { computed, onMounted, provide, ref, shallowRef, watch } from 'vue'
import { useNetworkStatus } from '@/composables/useNetworkStatus'
import { queueStore } from '@/stores/queueStore'
import { authService } from '@/services/authService'
import { radioStationStore } from '@/stores/radioStationStore'
import {
  ContextMenuKey,
  CurrentStreamableKey,
  DialogBoxKey,
  MessageToasterKey,
  ModalKey,
  OverlayKey,
} from '@/config/symbols'
import { useRouter } from '@/composables/useRouter'
import { commonStore } from '@/stores/commonStore'
import type { Route } from '@/router'

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
import ContextMenu from '@/components/ui/context-menu/ContextMenu.vue'

const HotkeyListener = defineAsyncComponent(() => import('@/components/utils/HotkeyListener.vue'))
const LoginForm = defineAsyncComponent(() => import('@/components/auth/LoginForm.vue'))
const MainWrapper = defineAsyncComponent(() => import('@/components/layout/main-wrapper/index.vue'))
const SupportKoel = defineAsyncComponent(() => import('@/components/meta/SupportKoel.vue'))
const AiAssistantScreen = defineAsyncComponent(() => import('@/components/ai/AiAssistantScreen.vue'))
const DropZone = defineAsyncComponent(() => import('@/components/ui/upload/DropZone.vue'))
const AcceptInvitation = defineAsyncComponent(() => import('@/components/invitation/AcceptInvitation.vue'))
const ResetPasswordForm = defineAsyncComponent(() => import('@/components/auth/ResetPasswordForm.vue'))
const Embed = defineAsyncComponent(() => import('@/components/embed/widget/EmbedWidget.vue'))

const overlay = ref<InstanceType<typeof Overlay>>()
const dialog = ref<InstanceType<typeof DialogBox>>()
const toaster = ref<InstanceType<typeof MessageToaster>>()
const currentStreamable = ref<Streamable>()
const showDropZone = ref(false)

const { isCurrentScreen, resolveRoute, triggerNotFound, onRouteChanged } = useRouter()
const { online } = useNetworkStatus()

const authenticated = ref(false)
const initialized = ref(false)
const currentRoute = ref<Route | null>(null)

const triggerAppInitialization = () => (authenticated.value = true)
const onInitError = () => (authenticated.value = false)

const onInitSuccess = async () => {
  initialized.value = true

  if (currentRoute.value && currentRoute.value.meta?.guard?.() === false) {
    triggerNotFound()
  }
}

const layout = computed(() => {
  if (currentRoute.value?.meta?.layout) {
    return currentRoute.value.meta.layout
  }

  return authenticated.value ? 'default' : 'login'
})

onMounted(() => {
  // Add an ugly mac/non-mac class for OS-targeting styles.
  document.documentElement.classList.add(navigator.userAgent.includes('Mac') ? 'mac' : 'non-mac')

  currentRoute.value = resolveRoute()

  if (currentRoute.value?.meta?.public) {
    // If the route is public (embed, login, reset password etc.) we don't need to check for authentication.
    return
  }

  // If the user is authenticated via a proxy, we have the token in the window object.
  // Simply forward it to the authService and continue with the normal flow.
  if (window.KOEL.auth_token) {
    authService.setTokensUsingCompositeToken(window.KOEL.auth_token)
  }

  // The app has just been initialized, check if we can get the user data with an already existing token
  if (authService.hasApiToken()) {
    triggerAppInitialization()
  }
})

const onDragOver = (e: DragEvent) => {
  showDropZone.value = Boolean(e.dataTransfer?.types.includes('Files')) && !isCurrentScreen('Upload')
}

watch(
  () => queueStore.current,
  song => (currentStreamable.value = song),
)

watch(
  () => radioStationStore.current,
  station => {
    if (station) {
      currentStreamable.value = station
    }
  },
)

onRouteChanged(route => (currentRoute.value = route))

const onDragEnd = () => (showDropZone.value = false)

const onDragLeave = (e: MouseEvent) => {
  if ((e.currentTarget as Node)?.contains?.(e.relatedTarget as Node)) {
    return
  }

  showDropZone.value = false
}

const onDrop = () => (showDropZone.value = false)

provide(OverlayKey, overlay)
provide(DialogBoxKey, dialog)
provide(MessageToasterKey, toaster)
provide(CurrentStreamableKey, currentStreamable)

provide(
  ContextMenuKey,
  shallowRef({
    component: null,
    position: { top: 0, left: 0 },
  }),
)

provide(
  ModalKey,
  shallowRef({
    component: null,
  }),
)
</script>

<style lang="postcss">
#dragGhost {
  @apply hidden py-2 pl-8 pr-3 rounded-md text-base fixed bg-k-bg border border-k-fg-10
  text-k-fg pointer-events-none z-50 whitespace-nowrap;
}

#copyArea {
  @apply absolute -left-full bottom-px w-px h-px no-hover:hidden;
}
</style>

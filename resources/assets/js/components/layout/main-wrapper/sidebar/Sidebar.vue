<template>
  <nav
    v-koel-clickaway="closeIfMobile"
    :class="{ collapsed: !expanded, 'tmp-showing': tmpShowing, showing: mobileShowing }"
    class="flex flex-col pb-4 fixed md:relative w-full md:w-k-sidebar-width z-10"
    @mouseenter="onMouseEnter"
    @mouseleave="onMouseLeave"
  >
    <section class="search-wrapper p-6">
      <SearchForm />
    </section>

    <section v-koel-overflow-fade class="py-0 overflow-y-auto space-y-8">
      <SidebarYourMusicSection />
      <SidebarPlaylistsSection />
      <SidebarManageSection v-if="showManageSection" />
    </section>

    <section v-if="!isPlus && isAdmin" class="p-6">
      <BtnUpgradeToPlus />
    </section>

    <SidebarToggleButton v-model="expanded" />
  </nav>
</template>

<script lang="ts" setup>
import { computed, ref, watch } from 'vue'
import { eventBus } from '@/utils'
import {
  useAuthorization,
  useKoelPlus,
  useRouter,
  useUpload,
  useLocalStorage
} from '@/composables'

import SidebarPlaylistsSection from './SidebarPlaylistsSection.vue'
import SearchForm from '@/components/ui/SearchForm.vue'
import BtnUpgradeToPlus from '@/components/koel-plus/BtnUpgradeToPlus.vue'
import SidebarYourMusicSection from './SidebarYourMusicSection.vue'
import SidebarManageSection from './SidebarManageSection.vue'
import SidebarToggleButton from '@/components/layout/main-wrapper/sidebar/SidebarToggleButton.vue'

const { onRouteChanged } = useRouter()
const { isAdmin } = useAuthorization()
const { allowsUpload } = useUpload()
const { isPlus } = useKoelPlus()
const { get: lsGet, set: lsSet } = useLocalStorage()

const mobileShowing = ref(false)
const expanded = ref(!lsGet('sidebar-collapsed', false))
watch(expanded, value => lsSet('sidebar-collapsed', !value))

const showManageSection = computed(() => isAdmin.value || allowsUpload.value)

const closeIfMobile = () => (mobileShowing.value = false)

let tmpShowingHandler: number | undefined
const tmpShowing = ref(false)

const onMouseEnter = () => {
  if (expanded.value) return;

  tmpShowingHandler = window.setTimeout(() => {
    if (expanded.value) return
    tmpShowing.value = true
  }, 500)
}

const onMouseLeave = (e: MouseEvent) => {
  if (!e.relatedTarget) {
    return
  }

  if (tmpShowingHandler) {
    clearTimeout(tmpShowingHandler)
    tmpShowingHandler = undefined
  }

  tmpShowing.value = false
}

onRouteChanged(_ => (mobileShowing.value = false))

/**
 * Listen to toggle sidebar event to show or hide the sidebar.
 * This should only be triggered on a mobile device.
 */
eventBus.on('TOGGLE_SIDEBAR', () => (mobileShowing.value = !mobileShowing.value))
</script>

<style lang="postcss" scoped>
@import '@/../css/partials/mixins.pcss';

nav {
  @apply bg-k-bg-secondary;
  -ms-overflow-style: -ms-autohiding-scrollbar;
  box-shadow: 0 0 5px 0 rgba(0, 0, 0, 0.1);

  &.collapsed {
    @apply w-[24px] transition-[width] duration-200;

    > *:not(.btn-toggle) {
      @apply hidden;
    }

    &.tmp-showing {
      @apply absolute h-screen z-50 bg-k-bg-primary w-k-sidebar-width shadow-2xl;

      > *:not(.btn-toggle) {
        @apply block;
      }
    }
  }

  @media screen and (max-width: 768px) {
    @mixin themed-background;
    z-index: 999;

    transform: translateX(-100vw);
    transition: transform .2s ease-in-out;
    height: calc(100vh - var(--header-height));

    &.showing {
      transform: translateX(0);
    }
  }
}
</style>

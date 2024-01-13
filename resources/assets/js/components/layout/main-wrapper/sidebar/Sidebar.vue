<template>
  <nav id="sidebar" v-koel-clickaway="closeIfMobile" :class="{ showing: mobileShowing }" class="side side-nav">
    <section class="search-wrapper">
      <SearchForm/>
    </section>

    <section class="menu-wrapper">
      <section class="music">
        <h1>Your Music</h1>

        <ul class="menu">
          <SidebarItem screen="Home" href="#/home" :icon="faHome">Home</SidebarItem>
          <QueueSidebarItem/>
          <SidebarItem screen="Songs" href="#/songs" :icon="faMusic">All Songs</SidebarItem>
          <SidebarItem screen="Albums" href="#/albums" :icon="faCompactDisc">Albums</SidebarItem>
          <SidebarItem screen="Artists" href="#/artists" :icon="faMicrophone">Artists</SidebarItem>
          <SidebarItem screen="Genres" href="#/genres" :icon="faTags">Genres</SidebarItem>
          <YouTubeSidebarItem v-show="showYouTube"/>
        </ul>
      </section>

      <PlaylistList/>

      <section v-if="showManageSection" class="manage">
        <h1>Manage</h1>

        <ul class="menu">
          <SidebarItem screen="Settings" href="#/settings" :icon="faTools" v-if="isAdmin">Settings</SidebarItem>
          <SidebarItem screen="Upload" href="#/upload" :icon="faUpload">Upload</SidebarItem>
          <SidebarItem screen="Users" href="#/users" :icon="faUsers" v-if="isAdmin">Users</SidebarItem>
        </ul>
      </section>
    </section>

    <section v-if="!isPlus && isAdmin" class="plus-wrapper">
      <BtnUpgradeToPlus/>
    </section>
  </nav>
</template>

<script lang="ts" setup>
import {
  faCompactDisc,
  faHome,
  faMicrophone,
  faMusic,
  faTags,
  faTools,
  faUpload,
  faPlus,
  faUsers
} from '@fortawesome/free-solid-svg-icons'

import { computed, ref } from 'vue'
import { eventBus } from '@/utils'
import { useAuthorization, useKoelPlus, useRouter, useThirdPartyServices, useUpload } from '@/composables'

import SidebarItem from './SidebarItem.vue'
import QueueSidebarItem from './QueueSidebarItem.vue'
import YouTubeSidebarItem from './YouTubeSidebarItem.vue'
import PlaylistList from './PlaylistSidebarList.vue'
import SearchForm from '@/components/ui/SearchForm.vue'
import BtnUpgradeToPlus from '@/components/koel-plus/BtnUpgradeToPlus.vue'

const { onRouteChanged } = useRouter()
const { useYouTube } = useThirdPartyServices()
const { isAdmin } = useAuthorization()
const { allowsUpload } = useUpload()
const { isPlus } = useKoelPlus()

const mobileShowing = ref(false)
const youTubePlaying = ref(false)

const showYouTube = computed(() => useYouTube.value && youTubePlaying.value)
const showManageSection = computed(() => isAdmin.value || allowsUpload.value)

const closeIfMobile = () => (mobileShowing.value = false)

onRouteChanged(_ => (mobileShowing.value = false))

/**
 * Listen to toggle sidebar event to show or hide the sidebar.
 * This should only be triggered on a mobile device.
 */
eventBus.on('TOGGLE_SIDEBAR', () => (mobileShowing.value = !mobileShowing.value))
  .on('PLAY_YOUTUBE_VIDEO', _ => (youTubePlaying.value = true))
</script>

<style lang="scss" scoped>
nav {
  position: relative;
  width: var(--sidebar-width);
  background-color: var(--color-bg-secondary);
  overflow: auto;
  overflow-x: hidden;
  -ms-overflow-style: -ms-autohiding-scrollbar;
  box-shadow: 0 0 5px 0 rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;

  form[role=search] {
    min-height: 38px;
  }

  .search-wrapper {
    padding: 2.05rem 1.5rem 0;
  }

  .menu-wrapper {
    flex: 1;
    padding: 2.05rem 1.5rem;
    overflow-y: auto;

    @media (hover: none) {
      // Enable scroll with momentum on touch devices
      overflow-y: scroll;
      -webkit-overflow-scrolling: touch;
    }
  }

  .plus-wrapper {
    padding:  1rem 1.5rem;
  }

  .menu-wrapper > * + * {
    margin-top: 2.25rem;
  }

  .droppable {
    box-shadow: inset 0 0 0 1px var(--color-accent);
    border-radius: 4px;
    cursor: copy;
  }

  .queue > span {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    flex: 1;
  }

  :deep(h1) {
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
  }

  :deep(a svg) {
    opacity: .7;
  }

  :deep(a) {
    display: flex;
    align-items: center;
    gap: .7rem;
    height: 36px;
    line-height: 36px;
    white-space: nowrap;
    text-overflow: ellipsis;
    position: relative;

    &:active {
      padding: 2px 0 0 2px;
    }

    &.active, &:hover {
      color: var(--color-text-primary);
    }

    &.active {
      &::before {
        content: '';
        position: absolute;
        top: 25%;
        right: -1.5rem;
        width: 4px;
        height: 50%;
        background-color: var(--color-highlight);
        box-shadow: 0 0 40px 10px var(--color-highlight);
        border-radius: 9999rem;
      }
    }

    span {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }

  :deep(li li a) { // submenu items
    padding-left: 11px;

    &:active {
      padding: 2px 0 0 13px;
    }
  }

  @media screen and (max-width: 768px) {
    @include themed-background();
    transform: translateX(-100vw);
    transition: transform .2s ease-in-out;

    position: fixed;
    width: 100%;
    z-index: 99;
    height: calc(100vh - var(--header-height));

    &.showing {
      transform: translateX(0);
    }
  }
}
</style>

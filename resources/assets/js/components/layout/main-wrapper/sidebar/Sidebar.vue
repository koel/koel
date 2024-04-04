<template>
  <nav
    id="sidebar"
    v-koel-clickaway="closeIfMobile"
    :class="{ collapsed, 'tmp-showing': tmpShowing, showing: mobileShowing }"
    class="side side-nav"
    @mouseenter="onMouseEnter"
    @mouseleave="onMouseLeave"
  >
    <section class="search-wrapper">
      <SearchForm />
    </section>

    <section v-koel-overflow-fade class="menu-wrapper">
      <section class="music">
        <h1>Your Music</h1>

        <ul class="menu">
          <SidebarItem screen="Home" href="#/home" :icon="faHome">Home</SidebarItem>
          <QueueSidebarItem />
          <SidebarItem screen="Songs" href="#/songs" :icon="faMusic">All Songs</SidebarItem>
          <SidebarItem screen="Albums" href="#/albums" :icon="faCompactDisc">Albums</SidebarItem>
          <SidebarItem screen="Artists" href="#/artists" :icon="faMicrophone">Artists</SidebarItem>
          <SidebarItem screen="Genres" href="#/genres" :icon="faTags">Genres</SidebarItem>
          <YouTubeSidebarItem v-show="showYouTube" />
        </ul>
      </section>

      <PlaylistList />

      <section v-if="showManageSection" class="manage">
        <h1>Manage</h1>

        <ul class="menu">
          <SidebarItem v-if="isAdmin" screen="Settings" href="#/settings" :icon="faTools">Settings</SidebarItem>
          <SidebarItem screen="Upload" href="#/upload" :icon="faUpload">Upload</SidebarItem>
          <SidebarItem v-if="isAdmin" screen="Users" href="#/users" :icon="faUsers">Users</SidebarItem>
        </ul>
      </section>
    </section>

    <section v-if="!isPlus && isAdmin" class="plus-wrapper">
      <BtnUpgradeToPlus />
    </section>

    <button class="btn-toggle" @click.prevent="toggleNavbar">
      <Icon v-if="collapsed" :icon="faAngleRight" />
      <Icon v-else :icon="faAngleLeft" />
    </button>
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
  faUsers,
  faAngleLeft,
  faAngleRight
} from '@fortawesome/free-solid-svg-icons'

import { computed, ref } from 'vue'
import { eventBus } from '@/utils'
import { useAuthorization, useKoelPlus, useRouter, useThirdPartyServices, useUpload, useLocalStorage } from '@/composables'

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
const { get: lsGet, set: lsSet } = useLocalStorage()

const collapsed = ref(lsGet('sidebar-collapsed', false))
const mobileShowing = ref(false)
const youTubePlaying = ref(false)

const showYouTube = computed(() => useYouTube.value && youTubePlaying.value)
const showManageSection = computed(() => isAdmin.value || allowsUpload.value)

const closeIfMobile = () => (mobileShowing.value = false)
const toggleNavbar = () => {
  collapsed.value = !collapsed.value
  lsSet('sidebar-collapsed', collapsed.value)
}

let tmpShowingHandler: number | undefined
const tmpShowing = ref(false)

const onMouseEnter = () => {
  if (!collapsed.value)  return;

  tmpShowingHandler = window.setTimeout(() => {
    if (!collapsed.value) return
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
  .on('PLAY_YOUTUBE_VIDEO', _ => (youTubePlaying.value = true))
</script>

<style lang="postcss" scoped>
nav {
  position: relative;
  width: var(--sidebar-width);
  background-color: var(--color-bg-secondary);
  -ms-overflow-style: -ms-autohiding-scrollbar;
  box-shadow: 0 0 5px 0 rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  will-change: width;

  &.collapsed {
    transition: width .2s;
    width: 24px;

    > *:not(.btn-toggle) {
      display: none;
    }

    &.tmp-showing {
      position: absolute;
      background-color: var(--color-bg-primary);
      width: var(--sidebar-width);
      height: 100vh;
      z-index: 100;

      > *:not(.btn-toggle) {
        display: block;
      }
    }
  }

  form[role=search] {
    min-height: 38px;
  }

  .search-wrapper {
    padding: 1.8rem 1.5rem;
  }

  .menu-wrapper {
    flex: 1;
    padding: 0 1.5rem;
    overflow-y: auto;

    @media (hover: none) {
      /* Enable scroll with momentum on touch devices */
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

    &::before {
      content: '';
      right: -1.5rem;
      top: 25%;
      width: 4px;
      height: 50%;
      position: absolute;
      transition: box-shadow .5s ease-in-out, background-color .5s ease-in-out;
      border-radius: 9999rem;
    }

    &.active {
      &::before {
        background-color: var(--color-highlight);
        box-shadow: 0 0 40px 10px var(--color-highlight);
      }
    }

    span {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }

  :deep(li li a) { /* submenu items */
    padding-left: 11px;

    &:active {
      padding: 2px 0 0 13px;
    }
  }

  .btn-toggle {
    width: 24px;
    aspect-ratio: 1 / 1;
    position: absolute;
    color: var(--color-text-secondary);
    background-color: var(--color-bg-secondary);
    border: 1px solid var(--color-border);
    border-radius: 50%;
    right: -12px;
    top: 30px;
    z-index: 5;

    &:hover {
      color: var(--color-text-primary);
      background-color: var(--color-bg-secondary);
    }

    @media screen and (max-width: 768px) {
      display: none;
    }
  }

  @media screen and (max-width: 768px) {
    background-color: var(--color-bg-primary);
    background-image: var(--bg-image);
    background-attachment: var(--bg-attachment);
    background-size: var(--bg-size);
    background-position: var(--bg-position);

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

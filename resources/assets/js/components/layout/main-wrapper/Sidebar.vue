<template>
  <nav id="sidebar" :class="{ showing: mobileShowing }" class="side side-nav" v-koel-clickaway="closeIfMobile">
    <SearchForm/>
    <section class="music">
      <h1>Your Music</h1>

      <ul class="menu">
        <li>
          <a :class="['home', activeScreen === 'Home' ? 'active' : '']" href="#/home">
            <icon :icon="faHome" fixed-width/>
            Home
          </a>
        </li>
        <li
          :class="droppableToQueue && 'droppable'"
          @dragleave="onQueueDragLeave"
          @dragover="onQueueDragOver"
          @drop="onQueueDrop"
        >
          <a :class="['queue', activeScreen === 'Queue' ? 'active' : '']" href="#/queue">
            <icon :icon="faListOl" fixed-width/>
            Current Queue
          </a>
        </li>
        <li>
          <a :class="['songs', activeScreen === 'Songs' ? 'active' : '']" href="#/songs">
            <icon :icon="faMusic" fixed-width/>
            All Songs
          </a>
        </li>
        <li>
          <a :class="['albums', activeScreen === 'Albums' ? 'active' : '']" href="#/albums">
            <icon :icon="faCompactDisc" fixed-width/>
            Albums
          </a>
        </li>
        <li>
          <a :class="['artists', activeScreen === 'Artists' ? 'active' : '']" href="#/artists">
            <icon :icon="faMicrophone" fixed-width/>
            Artists
          </a>
        </li>
        <li>
          <a :class="['genres', activeScreen === 'Genres' ? 'active' : '']" href="#/genres">
            <icon :icon="faTags" fixed-width/>
            Genres
          </a>
        </li>
        <li v-if="useYouTube">
          <a :class="['youtube', activeScreen === 'YouTube' ? 'active' : '']" href="#/youtube">
            <icon :icon="faYoutube" fixed-width/>
            YouTube Video
          </a>
        </li>
      </ul>
    </section>

    <PlaylistList/>

    <section v-if="isAdmin" class="manage">
      <h1>Manage</h1>

      <ul class="menu">
        <li>
          <a :class="['settings', activeScreen === 'Settings' ? 'active' : '']" href="#/settings">
            <icon :icon="faTools" fixed-width/>
            Settings
          </a>
        </li>
        <li>
          <a :class="['upload', activeScreen === 'Upload' ? 'active' : '']" href="#/upload">
            <icon :icon="faUpload" fixed-width/>
            Upload
          </a>
        </li>
        <li>
          <a :class="['users', activeScreen === 'Users' ? 'active' : '']" href="#/users">
            <icon :icon="faUsers" fixed-width/>
            Users
          </a>
        </li>
      </ul>
    </section>
  </nav>
</template>

<script lang="ts" setup>
import {
  faCompactDisc,
  faHome,
  faListOl,
  faMicrophone,
  faMusic,
  faTags,
  faTools,
  faUpload,
  faUsers
} from '@fortawesome/free-solid-svg-icons'
import { faYoutube } from '@fortawesome/free-brands-svg-icons'
import { ref } from 'vue'
import { eventBus, requireInjection } from '@/utils'
import { queueStore } from '@/stores'
import { useAuthorization, useDroppable, useThirdPartyServices } from '@/composables'
import { RouterKey } from '@/symbols'

import PlaylistList from '@/components/playlist/PlaylistSidebarList.vue'
import SearchForm from '@/components/ui/SearchForm.vue'

const mobileShowing = ref(false)
const activeScreen = ref<ScreenName>()
const droppableToQueue = ref(false)

const { acceptsDrop, resolveDroppedSongs } = useDroppable(['songs', 'album', 'artist', 'playlist'])
const { useYouTube } = useThirdPartyServices()
const { isAdmin } = useAuthorization()

const onQueueDragOver = (event: DragEvent) => {
  if (!acceptsDrop(event)) return false

  event.preventDefault()
  event.dataTransfer!.dropEffect = 'move'
  droppableToQueue.value = true
}

const onQueueDragLeave = () => (droppableToQueue.value = false)

const onQueueDrop = async (event: DragEvent) => {
  droppableToQueue.value = false

  if (!acceptsDrop(event)) return false

  event.preventDefault()
  const songs = await resolveDroppedSongs(event) || []
  songs.length && queueStore.queue(songs)

  return false
}

const closeIfMobile = () => (mobileShowing.value = false)

const router = requireInjection(RouterKey)

router.onRouteChanged(route => {
  mobileShowing.value = false
  activeScreen.value = route.screen
})

/**
 * Listen to toggle sidebar event to show or hide the sidebar.
 * This should only be triggered on a mobile device.
 */
eventBus.on('TOGGLE_SIDEBAR', () => (mobileShowing.value = !mobileShowing.value))
</script>

<style lang="scss" scoped>
nav {
  width: var(--sidebar-width);
  background-color: var(--color-bg-secondary);
  padding: 2.05rem 1.5rem;
  overflow: auto;
  overflow-x: hidden;
  -ms-overflow-style: -ms-autohiding-scrollbar;
  box-shadow: 0 0 5px 0 rgba(0, 0, 0, 0.1);

  > * + * {
    margin-top: 2.25rem;
  }

  @media (hover: none) {
    // Enable scroll with momentum on touch devices
    overflow-y: scroll;
    -webkit-overflow-scrolling: touch;
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

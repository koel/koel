<template>
  <nav id="sidebar" :class="{ showing }" class="side side-nav">
    <section class="music">
      <h1>Your Music</h1>

      <ul class="menu">
        <li>
          <a :class="['home', activeScreen === 'Home' ? 'active' : '']" href="#!/home">
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
          <a :class="['queue', activeScreen === 'Queue' ? 'active' : '']" href="#!/queue">
            <icon :icon="faListOl" fixed-width/>
            Current Queue
          </a>
        </li>
        <li>
          <a :class="['songs', activeScreen === 'Songs' ? 'active' : '']" href="#!/songs">
            <icon :icon="faMusic" fixed-width/>
            All Songs
          </a>
        </li>
        <li>
          <a :class="['albums', activeScreen === 'Albums' ? 'active' : '']" href="#!/albums">
            <icon :icon="faCompactDisc" fixed-width/>
            Albums
          </a>
        </li>
        <li>
          <a :class="['artists', activeScreen === 'Artists' ? 'active' : '']" href="#!/artists">
            <icon :icon="faMicrophone" fixed-width/>
            Artists
          </a>
        </li>
        <li v-if="useYouTube">
          <a :class="['youtube', activeScreen === 'YouTube' ? 'active' : '']" href="#!/youtube">
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
          <a :class="['settings', activeScreen === 'Settings' ? 'active' : '']" href="#!/settings">
            <icon :icon="faTools" fixed-width/>
            Settings
          </a>
        </li>
        <li>
          <a :class="['upload', activeScreen === 'Upload' ? 'active' : '']" href="#!/upload">
            <icon :icon="faUpload" fixed-width/>
            Upload
          </a>
        </li>
        <li>
          <a :class="['users', activeScreen === 'Users' ? 'active' : '']" href="#!/users">
            <icon :icon="faUsers" fixed-width/>
            Users
          </a>
        </li>
      </ul>
    </section>
  </nav>
</template>

<script lang="ts" setup>
import isMobile from 'ismobilejs'
import {
  faCompactDisc,
  faHome,
  faListOl,
  faMicrophone,
  faMusic,
  faTools,
  faUpload,
  faUsers
} from '@fortawesome/free-solid-svg-icons'
import { faYoutube } from '@fortawesome/free-brands-svg-icons'
import { ref } from 'vue'
import { eventBus, requireInjection } from '@/utils'
import { queueStore } from '@/stores'
import { useAuthorization, useDroppable, useThirdPartyServices } from '@/composables'
import { ActiveScreenKey } from '@/symbols'

import PlaylistList from '@/components/playlist/PlaylistSidebarList.vue'

const showing = ref(!isMobile.phone)
const activeScreen = requireInjection(ActiveScreenKey, ref('Home'))
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

eventBus.on({
  /**
   * On mobile, hide the sidebar whenever a screen is activated.
   */
  ACTIVATE_SCREEN: () => isMobile.phone && (showing.value = false),

  /**
   * Listen to toggle sidebar event to show or hide the sidebar.
   * This should only be triggered on a mobile device.
   */
  TOGGLE_SIDEBAR: () => (showing.value = !showing.value)
})
</script>

<style lang="scss" scoped>
nav {
  flex: 0 0 256px;
  background-color: var(--color-bg-secondary);
  padding: 2.05rem 0;
  overflow: auto;
  overflow-x: hidden;
  -ms-overflow-style: -ms-autohiding-scrollbar;

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

  ::v-deep(h1) {
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 0 16px;
    margin-bottom: 12px;
  }

  ::v-deep(a) {
    display: flex;
    align-items: center;
    gap: .7rem;
    height: 36px;
    line-height: 36px;
    padding: 0 16px 0 12px;
    border-left: 4px solid transparent;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;

    &.active, &:hover {
      border-left-color: var(--color-highlight);
      color: var(--color-text-primary);
      background: rgba(255, 255, 255, .05);
      box-shadow: 0 1px 0 rgba(0, 0, 0, .1);
    }

    &:active {
      opacity: .5;
    }

    &:hover {
      border-left-color: var(--color-highlight);
    }
  }

  ::v-deep(li li a) { // submenu items
    padding-left: 24px;
  }

  @media only screen and (max-width: 667px) {
    @include themed-background();

    position: fixed;
    height: calc(100vh - var(--header-height) + var(--footer-height));
    width: 100%;
    z-index: 99;
    top: var(--header-height);
    left: -100%;
    transition: left .3s ease-in;

    &.showing {
      left: 0;
    }
  }
}
</style>

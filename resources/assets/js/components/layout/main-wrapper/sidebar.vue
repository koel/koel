<template>
  <nav class="side side-nav" id="sidebar" :class="{ showing: showing }">
    <section class="music">
      <h1>Your Music</h1>

      <ul class="menu">
        <li>
          <a :class="['home', currentView === 'Home' ? 'active' : '']" href="#!/home">Home</a>
        </li>
        <li>
          <a
            :class="['queue', currentView === 'Queue' ? 'active' : '']"
            href="#!/queue"
            v-koel-droppable="handleDrop"
          >Current Queue</a>
        </li>
        <li>
          <a :class="['songs', currentView === 'Songs' ? 'active' : '']" href="#!/songs">All Songs</a>
        </li>
        <li>
          <a :class="['albums', currentView === 'Albums' ? 'active' : '']" href="#!/albums">Albums</a>
        </li>
        <li>
          <a :class="['artists', currentView === 'Artists' ? 'active' : '']" href="#!/artists">
            Artists
          </a>
        </li>
        <li v-if="sharedState.useYouTube">
          <a :class="['youtube', currentView === 'YouTube' ? 'active' : '']" href="#!/youtube">
            YouTube Video
          </a>
        </li>
      </ul>
    </section>

    <playlist-list :current-view="currentView"/>

    <section v-if="userState.current.is_admin" class="manage">
      <h1>Manage</h1>

      <ul class="menu">
        <li>
          <a :class="['settings', currentView === 'Settings' ? 'active' : '']" href="#!/settings">Settings</a>
        </li>
        <li>
          <a :class="['upload', currentView === 'Upload' ? 'active' : '']" href="#!/upload">Upload</a>
        </li>
        <li>
          <a :class="['users', currentView === 'Users' ? 'active' : '']" href="#!/users">Users</a>
        </li>
      </ul>
    </section>
  </nav>
</template>

<script lang="ts">
import Vue from 'vue'
import isMobile from 'ismobilejs'

import { eventBus } from '@/utils'
import { sharedStore, userStore, songStore, queueStore } from '@/stores'

export default Vue.extend({
  components: {
    PlaylistList: () => import('@/components/playlist/sidebar-list.vue')
  },

  data: () => ({
    currentView: 'Home',
    userState: userStore.state,
    showing: !isMobile.phone,
    sharedState: sharedStore.state
  }),

  methods: {
    /**
     * Handle songs dropped to our Queue menu item.
     */
    handleDrop: (e: DragEvent): boolean => {
      if (!e.dataTransfer) {
        return false
      }

      if (!e.dataTransfer.getData('application/x-koel.text+plain')) {
        return false
      }

      const songs = songStore.byIds(e.dataTransfer.getData('application/x-koel.text+plain').split(','))

      if (!songs.length) {
        return false
      }

      queueStore.queue(songs)

      return false
    }
  },

  created (): void {
    eventBus.on('LOAD_MAIN_CONTENT', (view: MainViewName): void => {
      this.currentView = view

      // Hide the sidebar if on mobile
      if (isMobile.phone) {
        this.showing = false
      }
    })

    /**
     * Listen to sidebar:toggle event to show or hide the sidebar.
     * This should only be triggered on a mobile device.
     */
    eventBus.on('TOGGLE_SIDEBAR', (): void => {
      this.showing = !this.showing
    })
  }
})
</script>

<style lang="scss">
#sidebar {
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

  a.droppable {
    transform: scale(1.2);
    transition: .3s;
    transform-origin: center left;

    color: var(--color-text-primary);
    background-color: rgba(0, 0, 0, .3);
  }

  section {
    h1 {
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 0 16px;
      margin-bottom: 12px;

      i {
        float: right;
      }
    }

    a {
      display: block;
      height: 36px;
      line-height: 36px;
      padding: 0 12px 0 16px;
      border-left: 4px solid transparent;

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

      &::before {
        width: 24px;
        display: inline-block;
        font-family: FontAwesome;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
      }

      &.home::before {
        content: "\f015";
      }

      &.queue::before {
        content: "\f0cb";
      }

      &.songs::before {
        content: "\f001";
      }

      &.albums::before {
        content: "\f152";
      }

      &.artists::before {
        content: "\f130";
      }

      &.youtube::before {
        content: "\f16a";
      }

      &.settings::before {
        content: "\f013";
      }

      &.users::before {
        content: "\f0c0";
      }

      &.upload::before {
        content: "\f093";
      }
    }
  }

  @media only screen and (max-width : 667px) {
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

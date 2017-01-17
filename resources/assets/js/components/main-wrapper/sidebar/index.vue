<template>
  <nav class="side side-nav" id="sidebar" :class="{ showing: showing }">
    <section class="music">
      <h1>Your Music</h1>

      <ul class="menu">
        <li>
          <a :class="['home', currentView == 'home' ? 'active' : '']" href="/#!/home">Home</a>
        </li>
        <li>
          <a :class="['queue', currentView == 'queue' ? 'active' : '']"
            href="/#!/queue"
            @dragleave="removeDroppableState"
            @dragover.prevent="allowDrop"
            @drop.stop.prevent="handleDrop">Current Queue</a>
        </li>
        <li>
          <a :class="['songs', currentView == 'songs' ? 'active' : '']" href="/#!/songs">All Songs</a>
        </li>
        <li>
          <a :class="['albums', currentView == 'albums' ? 'active' : '']" href="/#!/albums">Albums</a>
        </li>
        <li>
          <a :class="['artists', currentView == 'artists' ? 'active' : '']" href="/#!/artists">Artists</a>
        </li>
        <li v-if="sharedState.useYouTube">
          <a :class="['youtube', currentView == 'youtubePlayer' ? 'active' : '']" href="/#!/youtube">YouTube Video</a>
        </li>
      </ul>
    </section>

    <playlists :current-view="currentView"/>

    <section v-if="user.current.is_admin" class="manage">
      <h1>Manage</h1>

      <ul class="menu">
        <li>
          <a :class="['settings', currentView == 'settings' ? 'active' : '']" href="/#!/settings">Settings</a>
        </li>
        <li>
          <a :class="['users', currentView == 'users' ? 'active' : '']" href="/#!/users">Users</a>
        </li>
      </ul>
    </section>

    <a
      :href="latestVersionUrl"
      target="_blank"
      v-if="user.current.is_admin && sharedState.currentVersion < sharedState.latestVersion"
      class="new-ver">
      Koel version {{ sharedState.latestVersion }} is available!
    </a>
  </nav>
</template>

<script>
import isMobile from 'ismobilejs'

import { event, $ } from '../../../utils'
import { sharedStore, userStore, songStore, queueStore } from '../../../stores'
import playlists from './playlists.vue'

export default {
  components: { playlists },

  data () {
    return {
      currentView: 'home',
      user: userStore.state,
      showing: !isMobile.phone,
      sharedState: sharedStore.state
    }
  },

  computed: {
    latestVersionUrl () {
      return `https://github.com/phanan/koel/releases/tag/${this.sharedState.latestVersion}`
    }
  },

  methods: {
    /**
     * Remove the droppable state when a dragleave event occurs on the playlist's DOM element.
     *
     * @param  {Object} e The dragleave event.
     */
    removeDroppableState (e) {
      $.removeClass(e.target, 'droppable')
    },

    /**
     * Add a "droppable" class and set the drop effect when an item is dragged over "Queue" menu.
     *
     * @param  {Object} e The dragover event.
     */
    allowDrop (e) {
      $.addClass(e.target, 'droppable')
      e.dataTransfer.dropEffect = 'move'

      return false
    },

    /**
     * Handle songs dropped to our Queue menu item.
     *
     * @param  {Object} e The event
     *
     * @return {Boolean}
     */
    handleDrop (e) {
      this.removeDroppableState(e)

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

  created () {
    event.on('main-content-view:load', view => {
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
    event.on('sidebar:toggle', () => {
      this.showing = !this.showing
    })
  }
}
</script>

<style lang="sass">
@import "../../../../sass/partials/_vars.scss";
@import "../../../../sass/partials/_mixins.scss";

#sidebar {
  flex: 0 0 256px;
  background-color: $colorSidebarBgr;
  padding: 22px 0 0;
  max-height: calc(100vh - #{$headerHeight + $footerHeight});
  overflow: auto;
  overflow-x: hidden;
  -ms-overflow-style: -ms-autohiding-scrollbar;

  html.touchevents & {
    // Enable scroll with momentum on touch devices
    overflow-y: scroll;
    -webkit-overflow-scrolling: touch;
  }

  a.droppable {
    transform: scale(1.2);
    transition: .3s;
    transform-origin: center left;

    color: $colorMainText;
    background-color: rgba(0, 0, 0, .3);
  }

  section {
    margin-bottom: 32px;

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
        border-left-color: $colorHighlight;
        color: $colorLinkHovered;
        background: rgba(255, 255, 255, .05);
        box-shadow: 0 1px 0 rgba(0, 0, 0, .1);
      }

      &:active {
        opacity: .5;
      }

      &:hover {
        border-left-color: darken($colorHighlight, 20%);
      }

      &::before {
        width: 24px;
        display: inline-block;
        font-family: FontAwesome;
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
    }
  }

  .new-ver {
    margin: 16px;
    padding: 16px;
    border: 1px solid $color2ndText;
    color: $colorMainText;
    opacity: .3;
    font-size: .9rem;
    display: block;
    transition: .3s;

    &:hover {
      opacity: .7;
    }
  }

  @media only screen and (max-width : 667px) {
    position: fixed;
    height: calc(100vh - #{$headerHeight + $footerHeight});
    padding-bottom: $footerHeight; // make sure the footer can never overlap the content
    width: 100%;
    z-index: 99;
    top: $headerHeight;
    left: -100%;
    transition: left .3s ease-in;

    &.showing {
      left: 0;
    }
  }
}
</style>


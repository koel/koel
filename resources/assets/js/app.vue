<template>
  <div id="app">
    <div id="main" tabindex="0" v-show="authenticated"
      @keydown.space="togglePlayback"
      @keydown.j = "playNext"
      @keydown.k = "playPrev"
      @keydown.f = "search"
      @keydown.mediaPrev = "playPrev"
      @keydown.mediaNext = "playNext"
      @keydown.mediaToggle = "togglePlayback"
    >
      <site-header></site-header>
      <main-wrapper></main-wrapper>
      <site-footer></site-footer>
      <overlay ref="overlay"></overlay>
      <edit-songs-form ref="editSongsForm"></edit-songs-form>
    </div>

    <div class="login-wrapper" v-if="!authenticated">
      <login-form></login-form>
    </div>
  </div>
</template>

<script>
import Vue from 'vue';
import $ from 'jquery';
import isMobile from 'ismobilejs';

import siteHeader from './components/site-header/index.vue';
import siteFooter from './components/site-footer/index.vue';
import mainWrapper from './components/main-wrapper/index.vue';
import overlay from './components/shared/overlay.vue';
import loginForm from './components/auth/login-form.vue';
import editSongsForm from './components/modals/edit-songs-form.vue';

import { event, showOverlay, hideOverlay, loadMainView, forceReloadWindow, url } from './utils';
import { sharedStore, queueStore, songStore, userStore, preferenceStore as preferences } from './stores';
import { playback, ls } from './services';
import { focusDirective, clickawayDirective } from './directives';

export default {
  components: { siteHeader, siteFooter, mainWrapper, overlay, loginForm, editSongsForm },

  data() {
    return {
      authenticated: false,
    };
  },

  mounted() {
    // The app has just been initialized, check if we can get the user data with an already existing token
    const token = ls.get('jwt-token');
    if (token) {
      this.authenticated = true;
      this.init();
    }

    // Create the element to be the ghost drag image.
    $('<div id="dragGhost"></div>').appendTo('body');

    // And the textarea to copy stuff
    $('<textarea id="copyArea"></textarea>').appendTo('body');

    // Add an ugly mac/non-mac class for OS-targeting styles.
    // I'm crying inside.
    $('html').addClass(navigator.userAgent.indexOf('Mac') !== -1 ? 'mac' : 'non-mac');
  },

  methods: {
    init() {
      showOverlay();

      // Make the most important HTTP request to get all necessary data from the server.
      // Afterwards, init all mandatory stores and services.
      sharedStore.init().then(() => {
        playback.init();
        hideOverlay();

        // Load the default view.
        loadMainView('home');

        // Ask for user's notification permission.
        this.requestNotifPermission();

        // To confirm or not to confirm closing, it's a question.
        window.onbeforeunload = e => {
          if (!preferences.confirmClosing) {
            return;
          }

          return 'You asked Koel to confirm before closing, so here it is.';
        };

        // Let all other components know we're ready.
        event.emit('koel:ready');
      }).catch(() => this.authenticated = false);
    },

    /**
     * Toggle playback when user presses Space key.
     *
     * @param {Object} e The keydown event
     */
    togglePlayback(e) {
      if ($(e.target).is('input,textarea,button,select')) {
        return true;
      }

      // Ah... Good ol' jQuery. Whatever play/pause control is there, we blindly click it.
      $('#mainFooter .play:visible, #mainFooter .pause:visible').click();
      e.preventDefault();
    },

    /**
     * Play the previous song when user presses K.
     *
     * @param {Object} e The keydown event
     */
    playPrev(e) {
      if ($(e.target).is('input,textarea')) {
        return true;
      }

      playback.playPrev();
      e.preventDefault();
    },

    /**
     * Play the next song when user presses J.
     *
     * @param {Object} e The keydown event
     */
    playNext(e) {
      if ($(e.target).is('input,textarea')) {
        return true;
      }

      playback.playNext();
      e.preventDefault();
    },

    /**
     * Put focus into the search field when user presses F.
     *
     * @param {Object} e The keydown event
     */
    search(e) {
      if ($(e.target).is('input,textarea') || e.metaKey || e.ctrlKey) {
        return true;
      }

      $('#searchForm input[type="search"]').focus().select();
      e.preventDefault();
    },

    /**
     * Request for notification permission if it's not provided and the user is OK with notifs.
     */
    requestNotifPermission() {
      if (window.Notification && preferences.notify && Notification.permission !== 'granted') {
        Notification.requestPermission(result => {
          if (result === 'denied') {
            preferences.notify = false;
          }
        });
      }
    },
  },

  created() {
    event.on({
      /**
       * When the user logs in, set the whole app to be "authenticated" and initialize it.
       */
       'user:loggedin': () => {
        this.authenticated = true;
        this.init();
      },

      /**
       * Shows the "Edit Song" form.
       *
       * @param {Array.<Object>} An array of songs to edit
       */
      'songs:edit': songs => this.$refs.editSongsForm.open(songs),

      /**
       * Log the current user out and reset the application state.
       */
      logout() {
        userStore.logout().then((r) => {
          ls.remove('jwt-token');
          forceReloadWindow();
        });
      },

      /**
       * Parse song ID from permalink and play.
       */
      'koel:ready': () => {
        const songId = url.parseSongId();
        if (!songId) return;
        const song = songStore.byId(songId);
        if (!song) return;

        if (isMobile.apple.device) {
          // Mobile Safari doesn't allow autoplay, so we just queue.
          queueStore.queue(song);
          loadMainView('queue');
        } else {
          playback.queueAndPlay(song);
        }
      },
    });
  },
};

// Register our custom key codes
Vue.config.keyCodes = {
  j: 74,
  k: 75,
  f: 70,
  mediaNext: 176,
  mediaPrev: 177,
  mediaToggle: 179
};

// …and the global directives
Vue.directive('koel-focus', focusDirective);
Vue.directive('koel-clickaway',clickawayDirective);
</script>

<style lang="sass">
@import "resources/assets/sass/partials/_vars.scss";
@import "resources/assets/sass/partials/_mixins.scss";
@import "resources/assets/sass/partials/_shared.scss";

#dragGhost {
  position: relative;
  display: inline-block;
  background: $colorGreen;
  padding: .8rem;
  border-radius: .2rem;
  color: #fff;
  font-family: $fontFamily;
  font-size: 1rem;
  font-weight: $fontWeight_Thin;

  /**
   * We can totally hide this element on touch devices, because there's
   * no drag and drop support there anyway.
   */
  html.touchevents & {
    display: none;
  }
}

#copyArea {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;

  html.touchevents & {
    display: none;
  }
}

#main, .login-wrapper {
  display: flex;
  min-height: 100vh;
  flex-direction: column;

  background: $colorMainBgr;
  color: $colorMainText;

  font-family: $fontFamily;
  font-size: 1rem;
  line-height: 1.5rem;
  font-weight: $fontWeight_Thin;

  padding-bottom: $footerHeight;
}

.login-wrapper {
  @include vertical-center();

  padding-bottom: 0;
}
</style>

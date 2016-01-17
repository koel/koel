<template>
    <div id="app" tabindex="0" v-show="authenticated"
        @keydown.space="togglePlayback"
        @keydown.j = "playNext"
        @keydown.k = "playPrev"
        @keydown.f = "search"
        @keydown.177 = "playPrev"
        @keydown.176 = "playNext"
        @keydown.179 = "togglePlayback"
    >
        <site-header></site-header>
        <main-wrapper></main-wrapper>
        <site-footer></site-footer>
        <overlay :state.sync="overlayState"></overlay>
    </div>

    <div class="login-wrapper" v-else>
        <login-form></login-form>
    </div>
</template>

<script>
    import $ from 'jquery';
    
    import siteHeader from './components/site-header/index.vue';
    import siteFooter from './components/site-footer/index.vue';
    import mainWrapper from './components/main-wrapper/index.vue';
    import overlay from './components/shared/overlay.vue';
    import loginForm from './components/auth/login-form.vue';

    import sharedStore from './stores/shared';
    import queueStore from './stores/queue';
    import preferenceStore from './stores/preference';
    import playback from './services/playback';
    import focusDirective from './directives/focus';
    import ls from './services/ls';

    export default {
        components: { siteHeader, siteFooter, mainWrapper, overlay, loginForm },

        replace: false,

        data() {
            return {
                prefs: preferenceStore.state,
                authenticated: false,

                overlayState: {
                    showing: true,
                    dismissable: false,
                    type: 'loading',
                    message: '',
                },
            };
        },

        ready() {
            // The app has just been initialized, check if we can get the user data with an already existing token
            var token = ls.get('jwt-token');
            if (token) {
                this.authenticated = true;
                this.init();
            }

            // Create the element to be the ghost drag image.
            $('<div id="dragGhost"></div>').appendTo('body');
        },

        methods: {
            init() {
                this.showOverlay();

                // Make the most important HTTP request to get all necessary data from the server.
                // Afterwards, init all mandatory stores and services.
                sharedStore.init(() => {
                    playback.init(this);

                    this.hideOverlay();

                    // Load the default view.
                    this.loadMainView('queue');

                    // Ask for user's notification permission.
                    this.requestNotifPermission();

                    // To confirm or not to confirm closing, it's a question.
                    window.onbeforeunload = e => {
                        if (!this.prefs.confirmClosing) {
                            return;
                        }

                        return 'You asked Koel to confirm before closing, so here it is.';
                    };

                    // Let all other compoenents know we're ready.
                    this.$broadcast('koel:ready');
                }, () => this.authenticated = false);
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
                if (window.Notification && this.prefs.notify && Notification.permission !== 'granted') {
                    Notification.requestPermission(result => {
                        if (result === 'denied') {
                            preferenceStore.set('notify', false);
                        }
                    });
                }
            },

            /**
             * Load (display) a main panel (view).
             *
             * @param {String} view     The view, which can be found under components/main-wrapper/main-content.
             * @param {...*}            Extra data to attach to the view.
             */
            loadMainView(view, ...args) {
                this.$broadcast('main-content-view:load', view, ...args);
            },

            /**
             * Load a playlist into the main panel.
             *
             * @param {Object} playlist The playlist object
             */
            loadPlaylist(playlist) {
                this.loadMainView('playlist', playlist);
            },

            /**
             * Load the Favorites view.
             */
            loadFavorites() {
                this.loadMainView('favorites');
            },

            /**
             * Load an album into the main panel.
             * 
             * @param  {Object} album The album object
             */
            loadAlbum(album) {
                this.loadMainView('album', album);
            },

            /**
             * Load an artist into the main panel.
             * 
             * @param  {Object} artist The artist object
             */
            loadArtist(artist) {
                this.loadMainView('artist', artist);
            },

            /**
             * Shows the overlay.
             * 
             * @param {String}  message     The message to display.
             * @param {String}  type        (loading|success|info|warning|error)
             * @param {Boolean} dismissable Whether to show the Close button
             */
            showOverlay(message = 'Just a little patience…', type = 'loading', dismissable = false) {
                this.overlayState.message = message;
                this.overlayState.type = type;
                this.overlayState.dismissable = dismissable;
                this.overlayState.showing = true;
            },

            /**
             * Hides the overlay.
             */
            hideOverlay() {
                this.overlayState.showing = false;
            },

            /**
             * Shows the close button, allowing the user to close the overlay.
             */
            setOverlayDimissable() {
                this.overlayState.dismissable = true;
            },

            /**
             * Log the current user out and reset the application state.
             */
            logout() {
                ls.remove('jwt-token');
                this.authenticated = false;
                playback.stop();
                queueStore.clear();
                this.loadMainView('queue');
                this.$broadcast('koel:teardown');
            },
        },

        events: {
            'user:loggedin': function () {
                this.authenticated = true;
                this.init();
            },
        },
    };

    /**
     * Modified version of orderBy that is case insensitive
     *
     * @source https://github.com/vuejs/vue/blob/dev/src/filters/array-filters.js
     */
    Vue.filter('caseInsensitiveOrderBy', (arr, sortKey, reverse) => {
        if (!sortKey) {
            return arr;
        }

        var order = (reverse && reverse < 0) ? -1 : 1
        
        // sort on a copy to avoid mutating original array
        return arr.slice().sort((a, b) => {
            a = Vue.util.isObject(a) ? Vue.parsers.path.getPath(a, sortKey) : a
            b = Vue.util.isObject(b) ? Vue.parsers.path.getPath(b, sortKey) : b

            a = a === undefined ? a : a.toLowerCase()
            b = b === undefined ? b : b.toLowerCase()

            return a === b ? 0 : a > b ? order : -order
        });
    });

    // Register the global directives
    Vue.directive('koel-focus', focusDirective);
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";
    @import "resources/assets/sass/partials/_shared.scss";

    #dragGhost {
        position: relative;
        display: inline-block;
        background: $colorGreen;
        padding: 10px;
        border-radius: 3px;
        color: #fff;
        font-family: $fontFamily;
        font-size: $fontSize;
        font-weight: $fontWeight_Thin;
    }

    #app, .login-wrapper {
        display: flex;
        min-height: 100vh;
        flex-direction: column;
        
        background: $colorMainBgr;
        color: $colorMainText;

        font-family: $fontFamily;
        font-size: $fontSize;
        line-height: $fontSize * 1.5;
        font-weight: $fontWeight_Thin;

        padding-bottom: $footerHeight;
    }

    .login-wrapper {
        @include vertical-center();

        padding-bottom: 0;
    }
</style>

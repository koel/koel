<template>
    <div id="app" tabindex="0" 
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
</template>

<script>
    import $ from 'jquery';
    
    import siteHeader from './components/site-header/index.vue';
    import siteFooter from './components/site-footer/index.vue';
    import mainWrapper from './components/main-wrapper/index.vue';
    import overlay from './components/shared/overlay.vue';

    import sharedStore from './stores/shared';
    import artistStore from './stores/artist';
    import playlistStore from './stores/playlist';
    import queueStore from './stores/queue';
    import userStore from './stores/user';
    import settingStore from './stores/setting';
    import preferenceStore from './stores/preference';
    import playback from './services/playback';

    export default {
        components: { siteHeader, siteFooter, mainWrapper, overlay },

        replace: false,

        data() {
            return {
                prefs: preferenceStore.state,

                overlayState: {
                    showing: true,
                    dismissable: false,
                    type: 'loading',
                    message: '',
                },
            };
        },

        ready() {
            this.showOverlay();

            // Make the most important HTTP request to get all necessary data from the server.
            // Afterwards, init all mandatory stores and services.
            sharedStore.init(() => {
                this.initStores();
                playback.init(this);

                this.hideOverlay();

                // Ask for user's notificatio permission.
                this.requestNotifPermission();

                // Let all other compoenents know we're ready.
                this.$broadcast('koel:ready');
            });
        },

        methods: {
            /**
             * Initialize all stores to be used throughout the application.
             */
            initStores() {
                userStore.init();
                preferenceStore.init();
                
                // This will init album and song stores as well.
                artistStore.init();

                playlistStore.init();
                queueStore.init();
                settingStore.init();
            },

            /**
             * Toggle playback when user presses Space key.
             *
             * @param object e The keydown event
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
             * Play the prev song when user presses K.
             *
             * @param object e The keydown event
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
             * @param object e The keydown event
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
             * @param object e The keydown event
             */
            search(e) {
                if ($(e.target).is('input,textarea')) {
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
             * @param string view The view, which can be found under components/main-wrapper/main-content.
             */
            loadMainView(view) {
                this.$broadcast('main-content-view:load', view);
            },

            /**
             * Load a playlist into the main panel.
             *
             * @param object playlist The playlist object
             */
            loadPlaylist(playlist) {
                this.$broadcast('playlist:load', playlist);
                this.loadMainView('playlist');
            },

            /**
             * Load the Favorites view.
             */
            loadFavorites() {
                this.loadMainView('favorites');
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
    Vue.directive('koel-focus', require('./directives/focus'));
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #app {
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
</style>

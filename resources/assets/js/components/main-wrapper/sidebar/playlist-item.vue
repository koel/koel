<template>
    <li @dblclick.prevent="edit" class="playlist" :class="[type, editing ? 'editing' : '']">
        <a @click.prevent="load"
            @dragleave="removeDroppableState"
            @dragover.prevent="allowDrop"
            @drop.stop.prevent="handleDrop"
            :class="{ active: active }"
        >{{ playlist.name }}</a>

        <input type="text" 
            @keyup.esc="cancelEdit"
            @keyup.enter="update"
            @blur="update"
            v-model="playlist.name" 
            v-koel-focus="editing"
            required
        >
    </li>
</template>

<script>
    import $ from 'jquery';

    import songStore from '../../../stores/song';
    import playlistStore from '../../../stores/playlist';
    import favoriteStore from '../../../stores/favorite';
    
    export default {
        props: ['playlist', 'type'],

        data() {
            return {
                newName: '',
                editing: false,
                active: false,
            };
        },

        computed: {
            /**
             * Determine if the current menu item is the "Favorites" one.
             * 
             * @return {Boolean}
             */
            isFavorites() {
                return this.type === 'favorites';
            },
        },

        methods: {
            /**
             * Load songs in the current playlist.
             */
            load() {
                if (this.isFavorites) {
                    this.$root.loadFavorites();
                } else {
                    this.$root.loadPlaylist(this.playlist);
                }
            },

            /**
             * Show the form to edit the playlist.
             */
            edit() {
                if (this.isFavorites) {
                    return;
                }

                this.beforeEditCache = this.playlist.name;
                this.editing = true;
            },

            /**
             * Update the playlist's name.
             */
            update() {
                if (this.isFavorites ||  !this.editing) {
                    return;
                }

                this.editing = false;

                this.playlist.name = this.playlist.name.trim();
                if (!this.playlist.name) {
                    this.playlist.name = this.beforeEditCache;
                    return;
                }

                playlistStore.update(this.playlist);
            },

            /**
             * Cancel editing.
             */
            cancelEdit() {
                this.editing = false;
                this.playlist.name = this.beforeEditCache;
            },

            /**
             * Remove the droppable state when a dragleave event occurs on the playlist's DOM element. 
             * 
             * @param {Object} e The dragleave event.
             */
            removeDroppableState(e) {
                $(e.target).removeClass('droppable');
            },

            /**
             * Add a "droppable" class and set the drop effect when an item is dragged over the playlist's
             * DOM element.
             * 
             * @param {Object} e The dragover event.
             */
            allowDrop(e) {
                $(e.target).addClass('droppable');
                e.dataTransfer.dropEffect = 'move';

                return false;
            },

            /**
             * Handle songs dropped to our favorite or playlist menu item.
             * 
             * @param  {Object}   e        The event
             *
             * @return {Boolean}
             */
            handleDrop(e) {
                this.removeDroppableState(e);

                if (!e.dataTransfer.getData('text/plain')) {
                    return false;
                }

                var songs = songStore.byIds(e.dataTransfer.getData('text/plain').split(','));

                if (!songs.length) {
                    return false;
                }

                if (this.type === 'favorites') {
                    favoriteStore.like(songs);
                } else {
                    playlistStore.addSongs(this.playlist, songs);
                }

                return false;
            },
        },

        events: {
            'main-content-view:load': function (view, playlist) {
                if (view === 'favorites') {
                    this.active = this.isFavorites;
                } else if (view === 'playlist') {
                    this.active = this.playlist === playlist;      
                } else {
                    this.active = false;
                }
            },
        },
    };
</script>

<style lang="sass" scoped>
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    .playlist {
        user-select: none;

        a {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;

            span {
                pointer-events: none;
            }

            &::before {
                content: "\f0f6";
            }
        }

        &.favorites a::before {
            content: "\f004";
            color: $colorHeart;
        }

        input {
            display: none;

            width: calc(100% - 32px);
            margin: 5px 16px;
        }

        &.editing {
            a {
                display: none !important;
            }

            input {
                display: block;
            }
        }
    }
</style>

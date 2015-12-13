<template>
    <section id="playlists">
        <h1>Playlists <i class="fa fa-plus-circle control" @click="this.creating = !this.creating"></i></h1>

        <form v-show="creating" @submit.prevent="store" class="create">
            <input type="text" 
                @keyup.esc.prevent="creating = false"
                v-model="newName" 
                v-koel-focus="creating"
                placeholder="↵ to save"
                required
            >
        </form>

        <ul class="menu">
            <li>
                <a class="favorites" 
                    @click.prevent="loadFavorites"
                    :class="[currentView == 'favorites' ? 'active' : '']"
                    @dragleave="removeDroppableState"
                    @dragover.prevent="allowDrop"
                    @drop.stop="handleDrop(null, $event)">Favorites</a>
            </li>

            <li v-for="p in state.playlists" 
                @dblclick.prevent="edit(p)" 
                class="playlist"
                :class="{ editing: p == editedPlaylist }"
            >
                <a @click.prevent="load(p)"
                    @dragleave="removeDroppableState"
                    @dragover.prevent="allowDrop"
                    @drop.stop="handleDrop(p, $event)"

                    :class="[(currentView == 'playlist' && currentPlaylist == p)  ? 'active' : '']"
                >
                    {{ p.name }}
                </a>

                <input type="text" 
                    @keyup.esc="cancelEdit(p)"
                    @keyup.enter="update(p)"
                    @blur="update(p)"
                    v-model="p.name" 
                    v-koel-focus="p == editedPlaylist"
                    required
                >
            </li>
        </ul>
    </section>
</template>

<script>
    import songStore from '../../../stores/song';
    import playlistStore from '../../../stores/playlist';
    import favoriteStore from '../../../stores/favorite';
    import $ from 'jquery';
    
    export default {
        props: ['currentView'],

        data() {
            return {
                state: playlistStore.state,
                creating: false,
                newName: '',
                currentPlaylist: null,
                editedPlaylist: playlistStore.stub,
            };
        },

        methods: {
            /**
             * Load a playlist.
             * 
             * @param  object p The playlist.
             */
            load(p) {
                this.$root.loadPlaylist(this.currentPlaylist = p);
            },

            /**
             * Load the Favorite playlist.
             */
            loadFavorites() {
                this.currentPlaylist = null;
                this.$root.loadFavorites();
            },

            /**
             * Store/create a new playlist.
             */
            store() {
                this.creating = false;

                playlistStore.store(this.newName, [], () => {
                    // Reset the v-model
                    this.newName = '';
                });
            },

            /**
             * Show the form to edit a playlist.
             * 
             * @param  object p The playlist
             */
            edit(p) {
                this.beforeEditCache = p.name;
                this.editedPlaylist = p;
            },

            /**
             * Update a playlist's name.
             * 
             * @param  object p The playlist
             */
            update(p) {
                if (!this.editedPlaylist) {
                    return;
                }

                this.editedPlaylist = null;

                p.name = p.name.trim();
                if (!p.name) {
                    p.name = this.beforeEditCache;
                    return;
                }

                playlistStore.update(p);
            },

            /**
             * Cancel editing the currently edited playlist.
             * 
             * @param  object p The playlist.
             */
            cancelEdit(p) {
                this.editedPlaylist = null;
                p.name = this.beforeEditCache;
            },

            /**
             * Remove the droppable state when a dragleave event occurs on the playlist's DOM element. 
             * 
             * @param  object e The dragleave event.
             */
            removeDroppableState(e) {
                $(e.target).removeClass('droppable');
            },

            /**
             * Add a "droppable" class and set the drop effect when an item is dragged over the playlist's
             * DOM element.
             * 
             * @param  object e The dragover event.
             */
            allowDrop(e) {
                $(e.target).addClass('droppable');
                e.dataTransfer.dropEffect = 'move';

                return false;
            },

            /**
             * Handle songs dropped to our favorite or playlist menu item.
             * 
             * @param  object|null  playlist The playlist object, or null if dropping to Favorites.
             * @param  objevt       e        The event
             *
             * @return false
             */
            handleDrop(playlist, e) {
                this.removeDroppableState(e);

                var songs = songStore.byIds(e.dataTransfer.getData('text/plain').split(','));

                if (!songs.length) {
                    return false;
                }

                if (playlist) {
                    playlistStore.addSongs(playlist, songs);
                } else {
                    favoriteStore.like(songs);
                }

                return false;
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #playlists {
        .menu {
            a::before {
                content: "\f0f6";
            }

            a.favorites::before {
                content: "\f004";
                color: $colorHeart;
            }

            a.droppable {
                transform: scale(1.2);
                transition: .3s;
                transform-origin: center left;

                color: $colorMainText;
                background-color: rgba(0, 0, 0, .3);
            }

            .playlist {
                user-select: none;

                input {
                    display: none;

                    width: calc(100% - 32px);
                    margin: 5px 16px;
                }

                &.editing {
                    a {
                        display: none;
                    }

                    input {
                        display: block;
                    }
                }
            }
        }

        form.create {
            padding: 8px 16px;

            input[type="text"] {
                width: 100%;
            }
        }
    }
</style>

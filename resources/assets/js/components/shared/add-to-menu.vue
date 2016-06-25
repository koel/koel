<template>
    <div class="add-to-playlist" v-show="showing">
        <p>Add {{ songs.length | pluralize('song') }} to</p>

        <ul>
            <li v-if="mergedSettings.canQueue" @click="queueSongsAfterCurrent">After Current Song</li>
            <li v-if="mergedSettings.canQueue" @click="queueSongsToBottom">Bottom of Queue</li>
            <li v-if="mergedSettings.canQueue" @click="queueSongsToTop">Top of Queue</li>
            <li v-if="mergedSettings.canLike" @click="addSongsToFavorite">Favorites</li>
            <li v-for="playlist in playlistState.playlists" @click="addSongsToExistingPlaylist(playlist)">{{ playlist.name }}</li>
        </ul>

        <p>or create a new playlist</p>

        <form class="form-save form-simple" @submit.prevent="createNewPlaylistFromSongs">
            <input type="text"
                @keyup.esc.prevent="close"
                v-model="newPlaylistName"
                placeholder="Playlist name"
                required>
            <button type="submit">
                <i class="fa fa-save"></i>
            </button>
        </form>
    </div>
</template>

<script>
    import { assign, last } from 'lodash';

    import { pluralize, event, loadPlaylistView } from '../../utils';
    import { playlistStore } from '../../stores';
    import songMenuMethods from '../../mixins/song-menu-methods';

    export default {
        name: 'shared--add-to-menu',
        props: ['songs', 'showing', 'settings'],
        mixins: [songMenuMethods],
        filters: { pluralize },

        data() {
            return {
                newPlaylistName: '',
                playlistState: playlistStore.state,
                mergedSettings: assign({
                    canQueue: true,
                    canLike: true,
                }, this.settings),
            };
        },

        watch: {
            songs() {
                if (!this.songs.length) {
                    this.close();
                }
            },
        },

        methods: {
            /**
             * Save the selected songs as a playlist.
             * As of current we don't have selective save.
             */
            createNewPlaylistFromSongs() {
                this.newPlaylistName = this.newPlaylistName.trim();

                if (!this.newPlaylistName) {
                    return;
                }

                playlistStore.store(this.newPlaylistName, this.songs, () => {
                    this.newPlaylistName = '';

                    this.$nextTick(() => {
                        // Activate the new playlist right away
                        loadPlaylistView(last(this.playlistState.playlists));
                    });
                });

                this.close();
            },

            /**
             * Override the method from "songMenuMethods" mixin for this own logic.
             */
            close() {
                event.emit('add-to-menu:close');
            },
        },
    };
</script>

<style lang="sass" scoped>
    @import "../../../sass/partials/_vars.scss";
    @import "../../../sass/partials/_mixins.scss";

    .add-to-playlist {
        @include context-menu();

        position: absolute;
        padding: 8px;
        top: 36px;
        left: 0;
        width: 100%;

        p {
            margin: 4px 0;
            font-size: .9rem;

            &::first-of-type {
                margin-top: 0;
            }
        }

        $itemHeight: 28px;
        $itemMargin: 2px;

        ul {
            max-height: 5 * ($itemHeight + $itemMargin);
            overflow-y: scroll;
            -webkit-overflow-scrolling: touch;
        }

        li {
            background: rgba(255, 255, 255, .2);
            height: $itemHeight;
            line-height: $itemHeight;
            padding: 0 8px;
            margin: $itemMargin 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border-radius: 3px;
            background: #fff;

            &:hover {
                background: $colorHighlight;
                color: #fff;
            }
        }

        &::before {
            display: block;
            content: " ";
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-bottom: 10px solid rgb(232, 232, 232);
            position: absolute;
            top: -7px;
            left: calc(50% - 10px);
        }

        form {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;

            input[type="text"] {
                width: 100%;
                border-radius: 5px 0 0 5px;
                height: 28px;
            }

            button[type="submit"] {
                margin-top: 0;
                border-radius: 0 5px 5px 0 !important;
                height: 28px;
                margin-left: -2px !important;
            }
        }
    }
</style>

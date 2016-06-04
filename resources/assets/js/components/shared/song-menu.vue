<template>
    <ul v-el:menu class="menu song-menu" v-show="shown" tabindex="-1" @contextmenu.prevent
        @blur="close"
        :style="{ top: top + 'px', left: left + 'px' }"
    >
        <li v-if="onlyOneSongSelected" @click="doPlayback">
            <span v-if="songs[0].playbackState !== 'playing'">Play</span>
            <span v-else>Pause</span>
        </li>
        <li v-if="onlyOneSongSelected" @click="viewAlbumDetails(songs[0].album)">
            <span>Go to Album</span>
        </li>
        <li v-if="onlyOneSongSelected" @click="viewArtistDetails(songs[0].artist)">
            <span>Go to Artist</span>
        </li>
        <li class="has-sub">Add To
            <ul class="menu submenu">
                <li @click="queueSongsAfterCurrent">After Current Song</li>
                <li @click="queueSongsToBottom">Bottom of Queue</li>
                <li @click="queueSongsToTop">Top of Queue</li>
                <li class="separator"></li>
                <li @click="addSongsToFavorite">Favorites</li>
                <li class="separator" v-show="playlistState.playlists.length"></li>
                <li v-for="playlist in playlistState.playlists"
                    @click="addSongsToExistingPlaylist(playlist)"
                >{{ playlist.name }}</li>
            </ul>
        </li>
        <li v-if="isAdmin" @click="openEditForm">Edit</li>
        <li @click="download" v-if="sharedState.allowDownload">Download</li>
    </ul>
</template>

<script>
    import $ from 'jquery';

    import songMenuMethods from '../../mixins/song-menu-methods';
    import artistAlbumDetails from '../../mixins/artist-album-details';

    import queueStore from '../../stores/queue';
    import userStore from '../../stores/user';
    import playlistStore from '../../stores/playlist';
    import sharedStore from '../../stores/shared';
    import playback from '../../services/playback';
    import download from '../../services/download';

    export default {
        props: ['songs'],
        mixins: [songMenuMethods, artistAlbumDetails],

        data() {
            return {
                playlistState: playlistStore.state,
                sharedState: sharedStore.state,
            };
        },

        computed: {
            onlyOneSongSelected() {
                return this.songs.length === 1;
            },

            isAdmin() {
                return userStore.current.is_admin;
            },
        },

        methods: {
            open(top = 0, left = 0) {
                if (!this.songs.length) {
                    return;
                }

                this.top = top;
                this.left = left;
                this.shown = true;

                this.$nextTick(() => {
                    // Make sure the menu isn't off-screen
                    if (this.$el.getBoundingClientRect().bottom > window.innerHeight) {
                        $(this.$el).css({
                            top: 'auto',
                            bottom: 0,
                        });
                    } else {
                        $(this.$el).css({
                            top: this.top,
                            bottom: 'auto',
                        });
                    }

                    this.$els.menu.focus();
                });
            },

            /**
             * Take the right playback action based on the current playback state.
             */
            doPlayback() {
                switch (this.songs[0].playbackState) {
                    case 'playing':
                        playback.pause();
                        break;
                    case 'paused':
                        playback.resume();
                        break;
                    default:
                        if (!queueStore.contains(this.songs[0])) {
                            queueStore.queueAfterCurrent(this.songs[0]);
                        }

                        playback.play(this.songs[0]);
                        break;
                }

                this.close();
            },

            /**
             * Trigger opening the "Edit Song" form/overlay.
             */
            openEditForm() {
                if (this.songs.length) {
                    this.$root.showEditSongsForm(this.songs);
                }

                this.close();
            },

            download() {
                download.fromSongs(this.songs);
                this.close();
            },
        },

        /**
         * On component ready(), we use some JavaScript to prepare the submenu triggering.
         * With this, we can catch when the submenus shown or hidden, and can make sure
         * they don't appear off-screen.
         */
        ready() {
            $(this.$el).find('.has-sub').hover(e => {
                const $submenu = $(e.target).find('.submenu:first');
                if (!$submenu.length) {
                    return;
                }

                $submenu.show();

                // Make sure the submenu isn't off-screen
                if ($submenu[0].getBoundingClientRect().bottom > window.innerHeight) {
                    $submenu.css({
                        top: 'auto',
                        bottom: 0,
                    });
                }
            }, e => {
                $(e.target).find('.submenu:first').hide().css({
                    top: 0,
                    bottom: 'auto',
                });
            });
        },
    };
</script>

<style lang="sass" scoped>
    @import "../../../sass/partials/_vars.scss";
    @import "../../../sass/partials/_mixins.scss";

    .menu {
        @include context-menu();
        position: fixed;

        li {
            position: relative;
            padding: 4px 12px;
            cursor: default;
            white-space: nowrap;

            &:hover {
                background: $colorOrange;
                color: #fff;
            }

            &.separator {
                pointer-event: none;
                padding: 1px 0;
                background: #ccc;
            }

            &.has-sub {
                padding-right: 24px;

                &:after {
                    position: absolute;
                    right: 12px;
                    top: 4px;
                    content: "▸";
                    width: 16px;
                    text-align: right;
                }
            }
        }

        .submenu {
            position: absolute;
            display: none;
            left: 100%;
            top: 0;
        }
    }
</style>

<template>
    <section id="playlistWrapper">
        <h1 class="heading">
            <span>{{ playlist.name }}
                <i class="fa fa-chevron-down toggler" 
                    v-show="isPhone && !showingControls" 
                    @click="showingControls = true"></i>
                <i class="fa fa-chevron-up toggler" 
                    v-show="isPhone && showingControls" 
                    @click.prevent="showingControls = false"></i>
            </span>

            <div class="buttons" v-show="!isPhone || showingControls">
                <button class="play-shuffle" 
                    @click.prevent="shuffle"
                    v-if="playlist.songs.length && selectedSongs.length < 2"
                >
                    <i class="fa fa-random"></i> All
                </button>
                <button class="play-shuffle" @click.prevent="shuffleSelected" v-if="selectedSongs.length > 1">
                    <i class="fa fa-random"></i> Selected
                </button>
                <button class="add-to" @click.prevent="showingAddToMenu = !showingAddToMenu" v-if="selectedSongs.length">
                    {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
                </button>
                <button class="del"
                    title="Delete this playlist"
                    @click.prevent="del">
                    <i class="fa fa-times"></i> Playlist
                </button>

                <add-to-menu 
                    :songs="selectedSongs" 
                    :showing="showingAddToMenu && playlist.songs.length"
                </add-to-menu>
            </div>
        </h1>

        <song-list v-show="playlist.songs.length" 
            :items="playlist.songs" 
            :selected-songs.sync="selectedSongs" 
            type="playlist" 
            :playlist="playlist">
        </song-list>

        <div v-show="!playlist.songs.length" class="none">
            The playlist is currently empty. You can fill it up by dragging songs into its name in the sidebar, 
            or use the &quot;Add To…&quot; button.
        </div>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';

    import songList from '../../shared/song-list.vue';
    import playlistStore from '../../../stores/playlist';
    import playback from '../../../services/playback';
    import shuffleSelectedMixin from '../../../mixins/shuffle-selected';
    import hasAddToMenuMixin from '../../../mixins/has-add-to-menu';

    export default {
        mixins: [shuffleSelectedMixin, hasAddToMenuMixin],

        components: { songList },

        data() {
            return {
                playlist: playlistStore.stub,
                isPhone: isMobile.phone,
                showingControls: false,
            };
        },

        events: {
            /**
             * Listen to 'playlist:load' event (triggered from $root currently)
             * to load the requested playlist into view.
             * 
             * @param  object playlist
             */
            'playlist:load': function (playlist) {
                this.playlist = playlist;
            },
        },

        methods: {
            /**
             * Shuffle the songs in the current playlist.
             */
            shuffle() {
                playback.queueAndPlay(this.playlist.songs, true);
            },

            /**
             * Delete the current playlist.
             */
            del() {
                playlistStore.delete(this.playlist, () => {
                    // Reset the current playlist to our stub, so that we don't encounter
                    // any property reference error.
                    this.playlist = playlistStore.stub;

                    // Switch back to Queue screen
                    this.$root.loadMainView('queue');
                });
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #playlistWrapper {
        button.play-shuffle, button.del {
            i {
                margin-right: 0 !important;
            }
        }

        button.del {
            background-color: $colorRed !important;

            &:hover {
                background-color: darken($colorRed, 10%) !important;
            }
        }

        .none {
            color: $color2ndText;
            padding: 16px 24px;

            a {
                color: $colorHighlight;
            }
        }
    }
</style>

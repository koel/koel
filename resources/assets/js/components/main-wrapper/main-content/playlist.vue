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
                    v-if="playlist.songs.length"
                >
                    <i class="fa fa-random"></i> All
                </button>
                <button class="del"
                    title="Delete this playlist"
                    @click.prevent="del">
                    <i class="fa fa-times"></i> Playlist
                </button>
            </div>
        </h1>

        <song-list :items="playlist.songs" type="playlist" :playlist="playlist"></song-list>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';

    import songList from '../../shared/song-list.vue';
    import playlistStore from '../../../stores/playlist';
    import playback from '../../../services/playback';

    export default {
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
    }
</style>

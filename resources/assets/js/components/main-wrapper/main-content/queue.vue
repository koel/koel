<template>
    <div id="queueWrapper">
        <h1 class="heading">
            <span title="That's a freaking lot of U's and E's">Current Queue
                <i class="fa fa-chevron-down toggler" 
                    v-show="isPhone && !showingControls" 
                    @click="showingControls = true"></i>
                <i class="fa fa-chevron-up toggler" 
                    v-show="isPhone && showingControls" 
                    @click.prevent="showingControls = false"></i>
            </span>

            <div class="buttons" v-show="!isPhone || showingControls">
                <button 
                    class="play-shuffle" 
                    @click.prevent="shuffle" 
                    v-if="state.songs.length > 1 && selectedSongs.length < 2"
                >
                    <i class="fa fa-random"></i> All
                </button>
                <button class="play-shuffle" @click.prevent="shuffleSelected" v-if="selectedSongs.length > 1">
                    <i class="fa fa-random"></i> Selected
                </button>
                <button class="save" 
                    @click.prevent="showAddToPlaylistDialog = !showAddToPlaylistDialog" 
                    v-if="state.songs.length > 1"
                >
                    {{ showAddToPlaylistDialog ? 'Cancel' : 'Add To…' }}
                </button>
                <button class="clear" @click.prevent="clear" v-if="state.songs.length">Clear</button>

                <add-to-playlist 
                    :songs="songsToAddToPlaylist" 
                    :showing.sync="showAddToPlaylistDialog">
                </add-to-playlist>
            </div>
        </h1>

        <song-list :items="state.songs" :selected-songs.sync="selectedSongs" type="queue"></song-list>
    </div>
</template>

<script>
    import _ from 'lodash';
    import isMobile from 'ismobilejs';
    
    import songList from '../../shared/song-list.vue';
    import addToPlaylist from '../../shared/add-to-playlist.vue';
    import playlistStore from '../../../stores/playlist';
    import queueStore from '../../../stores/queue';
    import playback from '../../../services/playback';
    import shuffleSelectedMixin from '../../../mixins/shuffle-selected';
    
    export default {
        mixins: [shuffleSelectedMixin],

        components: { songList, addToPlaylist },

        data() {
            return {
                state: queueStore.state,
                showAddToPlaylistDialog: false,
                playlistName: '',
                isPhone: isMobile.phone,
                showingControls: false,
            };
        },

        computed: {
            /**
             * If no songs are selected, we provide all queued songs as a tribute to playlist god.
             * 
             * @return {Array} The songs to add into a (new) playlist
             */
            songsToAddToPlaylist() {
                return this.selectedSongs.length ? this.selectedSongs : queueStore.all();
            },
        },

        watch: {
            /**
             * Watch the number of songs currently queued.
             * If we don't have any queuing song, the "Save to Playlist" form shouldn't be open.
             */
            'state.songs': function () {
                if (!queueStore.all().length) {
                    this.showAddToPlaylist = false;
                }
            },
        },

        methods: {
            /**
             * Shuffle the current queue.
             */
            shuffle() {
                playback.queueAndPlay(queueStore.shuffle());
            },

            /**
             * Clear the queue.
             */
            clear() {
                queueStore.clear();
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #queueWrapper {
        .none {
            color: $color2ndText;
            margin-top: 16px;
        }


        button.play-shuffle {
            i {
                margin-right: 0 !important;
            }
        }

        button.clear {
            background-color: $colorRed !important;

            &:hover {
                background-color: darken($colorRed, 10%) !important;
            }
        }

        button.save {
            background-color: $colorGreen !important;

            &:hover {
                background-color: darken($colorGreen, 10%) !important;
            }
        }

        @media only screen 
        and (max-device-width : 667px) 
        and (orientation : portrait) {
        }
    }
</style>

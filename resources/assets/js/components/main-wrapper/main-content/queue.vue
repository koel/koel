<template>
    <section id="queueWrapper">
        <h1 class="heading">
            <span title="That's a freaking lot of U's and E's">Current Queue
                <i class="fa fa-angle-down toggler"
                    v-show="isPhone && !showingControls"
                    @click="showingControls = true"></i>
                <i class="fa fa-angle-up toggler"
                    v-show="isPhone && showingControls"
                    @click.prevent="showingControls = false"></i>

                <span class="meta" v-show="meta.songCount">
                    {{ meta.songCount }} {{ meta.songCount | pluralize 'song' }}
                    •
                    {{ meta.totalLength }}
                </span>
            </span>

            <div class="buttons" v-show="!isPhone || showingControls">
                <button
                    class="play-shuffle btn btn-orange"
                    @click.prevent="shuffle"
                    v-if="state.songs.length > 1 && selectedSongs.length < 2"
                >
                    <i class="fa fa-random"></i> All
                </button>
                <button class="play-shuffle btn btn-orange" @click.prevent="shuffleSelected" v-if="selectedSongs.length > 1">
                    <i class="fa fa-random"></i> Selected
                </button>
                <button class="btn btn-green" @click.prevent.stop="showingAddToMenu = !showingAddToMenu" v-if="state.songs.length > 1">
                    {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
                </button>
                <button class="btn btn-red" @click.prevent="clear" v-if="state.songs.length">Clear</button>

                <add-to-menu
                    :songs="songsToAddTo"
                    :showing="showingAddToMenu && state.songs.length"
                    :settings="{ canQueue: false }">
                </add-to-menu>
            </div>
        </h1>

        <song-list
            v-show="state.songs.length"
            :items="state.songs"
            :selected-songs.sync="selectedSongs"
            :sortable="false"
            type="queue">
        </song-list>

        <div v-show="!state.songs.length" class="none">
            <p>Empty spaces. Abandoned places.</p>

            <p v-if="showShufflingAllOption">How about
                <a class="start" @click.prevent="shuffleAll">shuffling all songs</a>?
            </p>
        </div>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';

    import queueStore from '../../../stores/queue';
    import songStore from '../../../stores/song';
    import playback from '../../../services/playback';
    import hasSongList from '../../../mixins/has-song-list';

    export default {
        mixins: [hasSongList],

        data() {
            return {
                state: queueStore.state,
                showingAddToMenu: false,
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
            songsToAddTo() {
                return this.selectedSongs.length ? this.selectedSongs : queueStore.all();
            },

            /**
             * Determine if we should display a "Shuffling All" link.
             * This should be true if:
             * - The current list is queue, and
             * - We have songs to shuffle.
             */
            showShufflingAllOption() {
                return songStore.all().length;
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
             * Shuffle all songs we have.
             */
            shuffleAll() {
                playback.queueAndPlay(songStore.all(), true);
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
    @import "../../../../sass/partials/_vars.scss";
    @import "../../../../sass/partials/_mixins.scss";

    #queueWrapper {
        .none {
            color: $color2ndText;
            padding: 16px 24px;

            a {
                color: $colorHighlight;
            }
        }


        button.play-shuffle {
            i {
                margin-right: 0 !important;
            }
        }

        @media only screen and (max-device-width : 667px) {
        }
    }
</style>

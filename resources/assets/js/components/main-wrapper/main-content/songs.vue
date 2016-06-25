<template>
    <section id="songsWrapper">
        <h1 class="heading">
            <span>All Songs
                <i class="fa fa-angle-down toggler"
                    v-show="isPhone && !showingControls"
                    @click="showingControls = true"></i>
                <i class="fa fa-angle-up toggler"
                    v-show="isPhone && showingControls"
                    @click.prevent="showingControls = false"></i>

                <span class="meta" v-show="meta.songCount">
                    {{ meta.songCount | pluralize('song') }}
                    •
                    {{ meta.totalLength }}
                </span>
            </span>

            <div class="buttons" v-show="!isPhone || showingControls">
                <button
                    class="play-shuffle btn btn-orange"
                    @click.prevent="shuffle"
                    v-if="state.songs.length && selectedSongs.length < 2"
                >
                    <i class="fa fa-random"></i> All
                </button>
                <button class="play-shuffle btn btn-orange" @click.prevent="shuffleSelected" v-if="selectedSongs.length > 1">
                    <i class="fa fa-random"></i> Selected
                </button>

                <button class="btn btn-green" @click.prevent.stop="showingAddToMenu = !showingAddToMenu" v-if="selectedSongs.length">
                    {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
                </button>

                <add-to-menu :songs="selectedSongs" :showing="showingAddToMenu"><add-to-menu>
            </div>
        </h1>

        <song-list :items="state.songs" type="allSongs"></song-list>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';

    import { pluralize } from '../../../utils';
    import { songStore } from '../../../stores';
    import { playback } from '../../../services';
    import hasSongList from '../../../mixins/has-song-list';

    export default {
        name: 'main-wrapper--main-content--songs',
        mixins: [hasSongList],
        filters: { pluralize },

        data() {
            return {
                state: songStore.state,
                isPhone: isMobile.phone,
                showingControls: false,
            };
        },

        methods: {
            /**
             * Shuffle all songs.
             */
            shuffle() {
                // A null here tells the method to shuffle all songs, which is what we want.
                playback.queueAndPlay(null, true);
            },
        },
    };
</script>

<style lang="sass">
    @import "../../../../sass/partials/_vars.scss";
    @import "../../../../sass/partials/_mixins.scss";
</style>


<template>
    <section id="songsWrapper">
        <h1 class="heading">
            <span>All Songs
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
                    v-if="state.songs.length && selectedSongs.length < 2"
                >
                    <i class="fa fa-random"></i> All
                </button>
                <button class="play-shuffle" @click.prevent="shuffleSelected" v-if="selectedSongs.length > 1">
                    <i class="fa fa-random"></i> Selected
                </button>

                <button class="add-to" @click.prevent="showingAddToMenu = !showingAddToMenu" v-if="selectedSongs.length">
                    {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
                </button>

                <add-to-menu 
                    :songs="selectedSongs" 
                    :showing="showingAddToMenu">
                </add-to-menu>
            </div>
        </h1>

        <song-list :items="state.songs" :selected-songs.sync="selectedSongs"></song-list>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';

    import songList from '../../shared/song-list.vue';
    import songStore from '../../../stores/song';
    import playback from '../../../services/playback';
    import shuffleSelectedMixin from '../../../mixins/shuffle-selected';
    import hasAddToMenuMixin from '../../../mixins/has-add-to-menu';

    export default {
        mixins: [shuffleSelectedMixin, hasAddToMenuMixin],

        components: { songList },

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
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #songsWrapper {
        .none {
            color: $color2ndText;
            margin-top: 16px;
        }
    }
</style>


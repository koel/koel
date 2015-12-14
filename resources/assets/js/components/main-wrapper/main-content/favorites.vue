<template>
    <section id="songsWrapper">
        <h1 class="heading">
            <span>Songs You Love
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
                    v-if="state.songs.length && selectedSongs.length < 2"
                >
                    <i class="fa fa-random"></i> All
                </button>
                <button class="play-shuffle" @click.prevent="shuffleSelected" v-if="selectedSongs.length > 1">
                    <i class="fa fa-random"></i> Selected
                </button>
            </div>
        </h1>

        <song-list :items="state.songs" :selected-songs.sync="selectedSongs" type="favorites"></song-list>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';
    
    import songList from '../../shared/song-list.vue';
    import favoriteStore from '../../../stores/favorite';
    import playback from '../../../services/playback';
    import shuffleSelectedMixin from '../../../mixins/shuffle-selected';
    
    export default {
        mixins: [shuffleSelectedMixin],

        components: { songList },

        data () {
            return {
                state: favoriteStore.state,
                isPhone: isMobile.phone,
                showingControls: false,
            };
        },

        methods: {
            /**
             * Shuffle the current favorite songs.
             */
            shuffle() {
                playback.queueAndPlay(this.state.songs, true);
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #songsWrapper {
        button.play-shuffle, button.del {
            i {
                margin-right: 0 !important;
            }
        }
    }
</style>

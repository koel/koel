<template>
    <section id="favoritesWrapper">
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
                <button class="add-to" @click.prevent="showingAddToMenu = !showingAddToMenu" v-if="selectedSongs.length">
                    {{ showingAddToMenu ? 'Cancel' : 'Add To…' }}
                </button>

                <add-to-menu 
                    :songs="selectedSongs" 
                    :showing.sync="showingAddToMenu"
                    :settings="{ canLike: false }">
                </add-to-menu>
            </div>
        </h1>

        <song-list :items="state.songs" :selected-songs.sync="selectedSongs" type="favorites"></song-list>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';
    
    import songList from '../../shared/song-list.vue';
    import addToMenu from '../../shared/add-to-menu.vue';
    import favoriteStore from '../../../stores/favorite';
    import playback from '../../../services/playback';
    import shuffleSelectedMixin from '../../../mixins/shuffle-selected';
    
    export default {
        mixins: [shuffleSelectedMixin],

        components: { songList, addToMenu },

        data () {
            return {
                state: favoriteStore.state,
                isPhone: isMobile.phone,
                showingControls: false,
                showingAddToMenu: false,
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

        watch: {
            /**
             * Watch the number of favorite songs.
             * If we don't have any, the "Add To..." menu shouldn't be left open.
             */
            'state.songs': function () {
                if (!this.state.songs.length) {
                    this.showingAddToMenu = false;
                }
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #favoritesWrapper {
        button.play-shuffle, button.del {
            i {
                margin-right: 0 !important;
            }
        }
    }
</style>

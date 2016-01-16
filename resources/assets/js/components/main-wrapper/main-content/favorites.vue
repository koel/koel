<template>
    <section id="favoritesWrapper">
        <h1 class="heading">
            <span>Songs You Love
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
                    :showing="showingAddToMenu && state.songs.length"
                    :settings="{ canLike: false }">
                <add-to-menu>
            </div>
        </h1>

        <song-list 
            v-show="state.songs.length" 
            :items="state.songs" 
            :selected-songs.sync="selectedSongs" 
            type="favorites">
        </song-list>

        <div v-show="!state.songs.length" class="none">
            Start loving! 
            Click the <i style="margin: 0 5px" class="fa fa-heart"></i> icon when a song is playing to add it 
            to this list.
        </div>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';
    
    import favoriteStore from '../../../stores/favorite';
    import playback from '../../../services/playback';
    import hasSongList from '../../../mixins/has-song-list';
    
    export default {
        mixins: [hasSongList],

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

    #favoritesWrapper {
        button.play-shuffle, button.del {
            i {
                margin-right: 0 !important;
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

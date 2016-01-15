<template>
    <section id="artistWrapper">
        <h1 class="heading">
            <span class="overview">
                <img :src="artist.image" width="64" height="64" class="cover">
                {{ artist.name }}
                <i class="fa fa-angle-down toggler" 
                    v-show="isPhone && !showingControls" 
                    @click="showingControls = true"></i>
                <i class="fa fa-angle-up toggler" 
                    v-show="isPhone && showingControls" 
                    @click.prevent="showingControls = false"></i>

                <span class="meta" v-show="meta.songCount">
                    {{ artist.albums.length }} {{ artist.albums.length | pluralize 'album' }}
                    •
                    {{ meta.songCount }} {{ meta.songCount | pluralize 'song' }}
                    • 
                    {{ meta.totalLength }}
                </span>
            </span>

            <div class="buttons" v-show="!isPhone || showingControls">
                <button class="play-shuffle" 
                    @click.prevent="shuffle"
                    v-if="selectedSongs.length < 2"
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
                    :showing="showingAddToMenu"
                </add-to-menu>
            </div>
        </h1>

        <song-list
            :items="artist.songs" 
            :selected-songs.sync="selectedSongs" 
            type="artist">
        </song-list>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';

    import artistStore from '../../../stores/artist';
    import playback from '../../../services/playback';
    import hasSongList from '../../../mixins/has-song-list';

    export default {
        mixins: [hasSongList],

        data() {
            return {
                artist: artistStore.stub,
                isPhone: isMobile.phone,
                showingControls: false,
            };
        },

        events: {
            /**
             * Listen to 'main-content-view:load' event (triggered from $root currently)
             * to load the requested artist into view if applicable.
             *
             * @param {string} view     The view's name
             * @param {Object} artist
             */
            'main-content-view:load': function (view, artist) {
                if (view === 'artist') {
                    artistStore.getSongsByArtist(artist);
                    this.artist = artist;
                }
            },
        },

        methods: {
            /**
             * Shuffle the songs by the current artist.
             */
            shuffle() {
                playback.queueAndPlay(this.artist.songs, true);
            },
        },
    };
</script>

<style lang="sass" scoped>
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #artistWrapper {
        button.play-shuffle {
            i {
                margin-right: 0 !important;
            }
        }

        .heading {
            .overview {
                position: relative;
                padding-left: 84px;

                @media only screen and (max-device-width : 768px) {
                    padding-left: 0;
                }
            }

            .cover {
                position: absolute;
                left: 0;
                top: -7px;

                @media only screen and (max-device-width : 768px) {
                    display: none;
                }
            }
        }
    }
</style>

<template>
    <section id="albumWrapper">
        <h1 class="heading">
            <span class="overview">
                <img :src="album.cover" width="64" height="64" class="cover">
                {{ album.name }}
                <i class="fa fa-angle-down toggler" 
                    v-show="isPhone && !showingControls" 
                    @click="showingControls = true"></i>
                <i class="fa fa-angle-up toggler" 
                    v-show="isPhone && showingControls" 
                    @click.prevent="showingControls = false"></i>

                <span class="meta" v-show="meta.songCount">
                    by <a class="artist" @click.prevent="viewArtistDetails">{{ album.artist.name }}</a>
                    •
                    {{ meta.songCount }} song{{ meta.songCount === 1 ? '' : 's' }} 
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
            :items="album.songs" 
            :selected-songs.sync="selectedSongs" 
            type="album">
        </song-list>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';

    import albumStore from '../../../stores/album';
    import playback from '../../../services/playback';
    import hasSongList from '../../../mixins/has-song-list';

    export default {
        mixins: [hasSongList],

        data() {
            return {
                album: albumStore.stub,
                isPhone: isMobile.phone,
                showingControls: false,
            };
        },

        events: {
            /**
             * Listen to 'album:load' event (triggered from $root currently)
             * to load the requested album into view.
             * 
             * @param  {Object} album
             */
            'album:load': function (album) {
                this.album = album;
            },
        },

        methods: {
            /**
             * Shuffle the songs in the current album.
             */
            shuffle() {
                playback.queueAndPlay(this.album.songs, true);
            },

            /**
             * Load the artist details screen.
             */
            viewArtistDetails() {
                this.$root.loadArtist(this.album.artist);
            },
        },
    };
</script>

<style lang="sass" scoped>
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #albumWrapper {
        button.play-shuffle {
            i {
                margin-right: 0 !important;
            }
        }

        .heading {
            .overview {
                position: relative;
                padding-left: 84px;
            }

            .cover {
                position: absolute;
                left: 0;
                top: -7px;
            }

            a.artist {
                color: $colorMainText;
                display: inline;

                &:hover {
                    color: $colorHighlight;
                }
            }
        }
    }
</style>

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

                    <template v-if="sharedState.allowDownload">
                        •
                        <a href="#" @click.prevent="download" title="Download all songs by this artist">Download</a>
                    </template>
                </span>
            </span>

            <div class="buttons" v-show="!isPhone || showingControls">
                <button class="play-shuffle btn btn-orange" @click.prevent="shuffle" v-if="selectedSongs.length < 2">
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

        <song-list :items="artist.songs" :selected-songs.sync="selectedSongs" type="artist"></song-list>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';

    import artistStore from '../../../stores/artist';
    import sharedStore from '../../../stores/shared';
    import playback from '../../../services/playback';
    import download from '../../../services/download';
    import hasSongList from '../../../mixins/has-song-list';

    export default {
        mixins: [hasSongList],

        data() {
            return {
                sharedState: sharedStore.state,
                artist: artistStore.stub,
                isPhone: isMobile.phone,
                showingControls: false,
            };
        },

        watch: {
            /**
             * Watch the artist's album count.
             * If this is changed to 0, the user has edit the songs by this artist
             * and move all of them to another artist (thus delete this artist entirely).
             * We should then go back to the artist list.
             */
            'artist.albums.length': function (newVal) {
                if (!newVal) {
                    this.$root.loadMainView('artists');
                }
            },
        },

        events: {
            /**
             * Listen to 'main-content-view:load' event (triggered from $root currently)
             * to load the requested artist into view if applicable.
             *
             * @param {String} view     The view's name
             * @param {Object} artist
             */
            'main-content-view:load': function (view, artist) {
                if (view === 'artist') {
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

            /**
             * Download all songs by the artist.
             */
            download() {
                download.fromArtist(this.artist);
            },
        },
    };
</script>

<style lang="sass" scoped>
    @import "../../../../sass/partials/_vars.scss";
    @import "../../../../sass/partials/_mixins.scss";

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

                @media only screen and (max-width : 768px) {
                    padding-left: 0;
                }
            }

            .cover {
                position: absolute;
                left: 0;
                top: -7px;

                @media only screen and (max-width : 768px) {
                    display: none;
                }
            }
        }
    }
</style>

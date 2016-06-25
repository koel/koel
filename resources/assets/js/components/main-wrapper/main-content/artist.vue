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
                    {{ artist.albums.length }} {{ artist.albums.length | pluralize('album') }}
                    •
                    {{ meta.songCount }} {{ meta.songCount | pluralize('song') }}
                    •
                    {{ meta.totalLength }}

                    <template v-if="sharedState.useLastfm">
                        •
                        <a href="#" @click.prevent="showInfo" title="View artist's extra information">Info</a>
                    </template>

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

        <song-list :items="artist.songs" type="artist"></song-list>

        <section class="info-wrapper" v-if="sharedState.useLastfm && info.showing">
            <a href="#" class="close" @click.prevent="info.showing = false"><i class="fa fa-times"></i></a>
            <div class="inner">
                <div class="loading" v-if="info.loading">
                    <sound-bar></sound-bar>
                </div>
                <artist-info :artist="artist" :mode="'full'" v-else></artist-info>
            </div>
        </section>
    </section>
</template>

<script>
    import isMobile from 'ismobilejs';

    import { pluralize, event, loadMainView } from '../../../utils';
    import sharedStore from '../../../stores/shared';
    import artistStore from '../../../stores/artist';
    import playback from '../../../services/playback';
    import artistInfoService from '../../../services/info/artist';
    import download from '../../../services/download';
    import hasSongList from '../../../mixins/has-song-list';
    import artistInfo from '../extra/artist-info.vue';
    import soundBar from '../../shared/sound-bar.vue';

    export default {
        name: 'main-wrapper--main-content--artist',
        mixins: [hasSongList],
        components: { artistInfo, soundBar },
        filters: { pluralize },

        data() {
            return {
                sharedState: sharedStore.state,
                artist: artistStore.stub,
                isPhone: isMobile.phone,
                showingControls: false,
                info: {
                    showing: false,
                    loading: true,
                },
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
                    loadMainView('artists');
                }
            },
        },

        created() {
            /**
             * Listen to 'main-content-view:load' event to load the requested artist
             * into view if applicable.
             *
             * @param {String} view     The view's name
             * @param {Object} artist
             */
            event.on('main-content-view:load', (view, artist) => {
                if (view === 'artist') {
                    this.info.showing = false;
                    this.artist = artist;
                }
            });
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

            showInfo() {
                this.info.showing = true;
                if (!this.artist.info) {
                    this.info.loading = true;
                    artistInfoService.fetch(this.artist, () => {
                        this.info.loading = false;
                    });
                } else {
                    this.info.loading = false;
                }
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

        @include artist-album-info-wrapper();
    }
</style>

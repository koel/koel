<template>
    <section id="homeWrapper">
        <h1 class="heading">
            <span>{{ greeting }}</span>
        </h1>

        <div class="main-scroll-wrap" v-el:wrapper @scroll="scrolling">
            <div class="top-sections">
                <section v-show="topSongs.length">
                    <h1>Most Played Songs</h1>

                    <ol class="top-song-list">
                        <li v-for="song in topSongs"
                            :top-play-count="topSongs[0].playCount"
                            :song="song"
                            is="song-item"></li>
                    </ol>
                </section>

                <section class="recent">
                    <h1>Recently Played</h1>

                    <ol class="recent-song-list" v-show="recentSongs.length">
                        <li v-for="song in recentSongs"
                            :top-play-count="topSongs[0].playCount"
                            :song="song"
                            is="song-item"></li>
                    </ol>

                    <p class="none" v-show="!recentSongs.length">
                        Your most-recent songs in this session will be displayed here.<br />
                        Start listening!
                    </p>
                </section>
            </div>

            <section class="top-artists" v-show="topArtists.length">
                <h1>Top Artists</h1>

                <div class="wrapper as-{{ preferences.artistsViewMode }}">
                    <artist-item v-for="artist in topArtists" :artist="artist"></artist-item>
                    <span class="item filler" v-for="n in 3"></span>
                </div>
            </section>

            <section class="top-albums as-{{ preferences.albumsViewMode }}" v-show="topAlbums.length">
                <h1>Top Albums</h1>

                <div class="wrapper">
                    <album-item v-for="album in topAlbums" :album="album"></album-item>
                    <span class="item filler" v-for="n in 3"></span>
                </div>
            </section>

            <to-top-button :showing="showBackToTop"></to-top-button>
        </div>
    </section>
</template>

<script>
    import { sample } from 'lodash';

    import songStore from '../../../stores/song';
    import albumStore from '../../../stores/album';
    import artistStore from '../../../stores/artist';
    import userStore from '../../../stores/user';
    import preferenceStore from '../../../stores/preference';
    import infiniteScroll from '../../../mixins/infinite-scroll';

    import albumItem from '../../shared/album-item.vue';
    import artistItem from '../../shared/artist-item.vue';
    import songItem from '../../shared/home-song-item.vue';

    export default {
        components: { albumItem, artistItem, songItem },
        /**
         * Note: We're not really using infinite scrolling here,
         * but only the handy "Back to Top" button.
         */
        mixins: [infiniteScroll],

        data () {
            return {
                greetings: [
                    'Oh hai!',
                    'Hey, %s!',
                    'Howdy, %s!',
                    'Yo!',
                    'How’s it going, %s?',
                    'Sup, %s?',
                    'How’s life, %s?',
                    'How’s your day, %s?',
                    'How have you been, %s?',
                ],
                recentSongs: [],
                topSongs: [],
                topAlbums: [],
                topArtists: [],

                preferences: preferenceStore.state,
            };
        },

        computed: {
            greeting() {
                return sample(this.greetings).replace('%s', userStore.current.name);
            },
        },

        methods: {
            /**
             * Refresh the dashboard with latest data.
             */
            refreshDashboard() {
                this.topSongs = songStore.getMostPlayed(7);
                this.topAlbums = albumStore.getMostPlayed(6);
                this.topArtists = artistStore.getMostPlayed(6);
                this.recentSongs = songStore.getRecent(7);
            },
        },

        events: {
            'koel:ready': function () {
                this.refreshDashboard();
            },

            'song:played': function () {
                this.refreshDashboard();
            },
        },
    };
</script>

<style lang="sass">
    @import "../../../../sass/partials/_vars.scss";
    @import "../../../../sass/partials/_mixins.scss";

    #homeWrapper {
        .top-sections {
            display: flex;

            > section {
                flex-grow: 1;
                flex-basis: 0;

                &:first-of-type {
                    margin-right: 8px;
                }
            }
        }

        .none {
            color: $color2ndText;
            padding: 0;

            a {
                color: $colorHighlight;
            }
        }

        .top-artists .wrapper, .top-albums .wrapper {
            @include artist-album-wrapper();
        }

        .main-scroll-wrap {
            section {
                margin-bottom: 48px;
            }

            h1 {
                font-size: 1.4rem;
                margin: 0 0 1.8rem;
                font-weight: $fontWeight_UltraThin;
            }
        }

        @media only screen and (max-width: 768px) {
            .top-sections {
                display: block;

                > section {
                    &:first-of-type {
                        margin-right: 0;
                    }
                }
            }
        }
    }
</style>

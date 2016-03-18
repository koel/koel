<template>
    <section id="homeWrapper">
        <h1 class="heading">
            <span>{{ greeting }}</span>
        </h1>

        <div class="main-scroll-wrap">
            <div class="top-sections">
                <section v-show="topSongs.length">
                    <h1>Most Played Songs</h1>

                    <ol class="top-song-list">
                        <li v-for="song in topSongs"
                            :class="{ playing: song.playbackState === 'playing' || song.playbackState === 'paused' }"
                            @dblclick.prevent="play(song)"
                        >
                            <span class="cover" :style="{ backgroundImage: 'url(' + song.album.cover + ')' }">
                                <a class="control" @click.prevent="triggerPlay(song)">
                                    <i class="fa fa-play" v-show="song.playbackState !== 'playing'"></i>
                                    <i class="fa fa-pause" v-else></i>
                                </a>
                            </span>
                            <span class="details">
                                <span :style="{ width: song.playCount * 100 / topSongs[0].playCount + '%' }"
                                    class="play-count"></span>
                                {{ song.title }}
                                <span class="by">{{ song.album.artist.name }} –
                                {{ song.playCount }} {{ song.playCount | pluralize 'play' }}</span>
                            </span>
                        </li>
                    </ol>
                </section>

                <section class="recent">
                    <h1>Recently Played</h1>

                    <ol class="recent-song-list" v-show="recentSongs.length">
                        <li v-for="song in recentSongs"
                            :class="{ playing: song.playbackState === 'playing' || song.playbackState === 'paused' }"
                            @dblclick.prevent="play(song)"
                        >
                            <span class="cover" :style="{ backgroundImage: 'url(' + song.album.cover + ')' }">
                                <a class="control" @click.prevent="triggerPlay(song)">
                                    <i class="fa fa-play" v-show="song.playbackState !== 'playing'"></i>
                                    <i class="fa fa-pause" v-else></i>
                                </a>
                            </span>
                            <span class="details">
                                {{ song.title }}
                                <span class="by">{{ song.album.artist.name }}</span>
                            </span>
                        </li>
                    </ol>

                    <p class="none" v-show="!recentSongs.length">
                        Your most-recent songs in this session will be displayed here.<br />
                        Start listening!
                    </p>
                </section>
            </div>

            <section class="top-artists" v-show="topArtists.length">
                <h1>Top Artists</h1>

                <div class="wrapper">
                    <artist-item v-for="artist in topArtists" :artist="artist"></artist-item>
                    <span class="item" v-for="n in 5"></span>
                </div>
            </section>

            <section class="top-albums" v-show="topAlbums.length">
                <h1>Top Albums</h1>

                <div class="wrapper">
                    <album-item v-for="album in topAlbums" :album="album"></album-item>
                    <span class="item" v-for="n in 5"></span>
                </div>
            </section>
        </div>
    </section>
</template>

<script>
    import _ from 'lodash';

    import playback from '../../../services/playback';
    import songStore from '../../../stores/song';
    import albumStore from '../../../stores/album';
    import artistStore from '../../../stores/artist';
    import userStore from '../../../stores/user';
    import queueStore from '../../../stores/queue';

    import albumItem from '../../shared/album-item.vue';
    import artistItem from '../../shared/artist-item.vue';

    export default {
        components: { albumItem, artistItem },

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
            };
        },

        computed: {
            greeting() {
                return _.sample(this.greetings).replace('%s', userStore.current.name);
            },
        },

        methods: {
            play(song) {
                if (!queueStore.contains(song)) {
                    queueStore.queueAfterCurrent(song);
                }

                playback.play(song);
            },

            /**
             * Trigger playing a song.
             */
            triggerPlay(song) {
                if (song.playbackState === 'stopped') {
                    this.play(song);
                } else if (song.playbackState === 'paused') {
                    playback.resume();
                } else {
                    playback.pause();
                }
            },

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

            ol li {
                display: flex;

                &.playing {
                    color: $colorHighlight;
                }

                &:hover .cover {
                    .control {
                        display: block;
                    }

                    &::before {
                        opacity: .7;
                    }
                }

                .cover {
                    flex: 0 0 48px;
                    height: 48px;
                    background-size: cover;
                    position: relative;

                    @include vertical-center();

                    &::before {
                        content: " ";
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        pointer-events: none;
                        background: #000;
                        opacity: 0;

                        html.touchevents & {
                            opacity: .7;
                        }
                    }

                    .control {
                        border-radius: 50%;
                        width: 28px;
                        height: 28px;
                        background: rgba(0, 0, 0, .7);
                        border: 1px solid transparent;
                        line-height: 26px;
                        font-size: 13px;
                        text-align: center;
                        z-index: 1;
                        display: none;
                        color: #fff;
                        transition: .3s;

                        &:hover {
                            transform: scale(1.2);
                            border-color: #fff;
                        }

                        html.touchevents & {
                            display: block;
                        }
                    }
                }

                .details {
                    flex: 1;
                    padding: 4px 8px;
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;

                    .play-count {
                        background: rgba(255, 255, 255, 0.08);
                        position: absolute;
                        height: 100%;
                        top: 0;
                        left: 0;
                        pointer-events: none;
                    }

                    .by {
                        display: block;
                        font-size: 90%;
                        opacity: .6;
                        margin-top: 2px;
                    }
                }

                //border-bottom: 1px solid $color2ndBgr;
                margin-bottom: 8px;
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
                font-size: 18px;
                margin: 0 0 24px;
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

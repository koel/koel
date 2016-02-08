<template>
    <section id="homeWrapper">
        <h1 class="heading">
            <span>{{ greeting }}</span>
        </h1>

        <div class="main-scroll-wrap">
            <section class="recent">
                <h1>Recently Played</h1>

                <song-list
                    v-show="songState.recent.length"
                    :items="songState.recent"
                    :sortable="false">
                </song-list>
                <p class="none" v-show="!songState.recent.length">No songs played yet. What ya waiting for?</p>
            </section>

            <section v-if="topSongs.length">
                <h1>Most Played Songs</h1>

                <song-list :items="topSongs" :sortable="false"></song-list>
            </section>

            <section class="top-artists" v-if="topArtists.length">
                <h1>Top Artists</h1>

                <div class="wrapper">
                    <artist-item v-for="artist in topArtists" :artist="artist"></artist-item>
                    <span class="item"></span>
                </div>
            </section>

            <section class="top-albums" v-if="topAlbums.length">
                <h1>Top Albums</h1>

                <div class="wrapper">
                    <album-item v-for="album in topAlbums" :album="album"></album-item>
                    <span class="item"></span>
                </div>
            </section>
        </div>
    </section>
</template>

<script>
    import _ from 'lodash';
    import isMobile from 'ismobilejs';

    import playback from '../../../services/playback';
    import songStore from '../../../stores/song';
    import albumStore from '../../../stores/album';
    import artistStore from '../../../stores/artist';
    import userStore from '../../../stores/user';

    import albumItem from '../../shared/album-item.vue';
    import artistItem from '../../shared/artist-item.vue';

    import hasSongList from '../../../mixins/has-song-list';

    export default {
        mixins: [hasSongList],
        components: { albumItem, artistItem },

        data () {
            return {
                isPhone: isMobile.phone,
                songState: songStore.state,
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
            };
        },

        computed: {
            greeting() {
                return _.sample(this.greetings).replace('%s', userStore.current().name);
            },

            topSongs() {
                return songStore.getMostPlayed();
            },

            topAlbums() {
                return albumStore.getMostPlayed();
            },

            topArtists() {
                return artistStore.getMostPlayed();
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #homeWrapper {
        button.play-shuffle, button.del {
            i {
                margin-right: 0 !important;
            }
        }

        .song-list-wrap {
            padding: 0 !important;
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
    }
</style>

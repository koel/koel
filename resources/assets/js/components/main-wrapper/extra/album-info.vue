<template>
    <article v-if="album.id" id="albumInfo">
        <h1>
            <span>{{ album.name }}</span>

            <a class="shuffle" @click.prevent="shuffleAll"><i class="fa fa-random"></i></a>
        </h1>

        <div v-if="album.info">
            <img v-if="album.info.image" :src="album.info.image"
                title=""
                class="cover">

            <div class="wiki" v-if="album.info.wiki && album.info.wiki.summary">
                <div class="summary" v-show="!showingFullWiki">{{{ album.info.wiki.summary }}}</div>
                <div class="full" v-show="showingFullWiki">{{{ album.info.wiki.full }}}</div>

                <button class="more" v-show="!showingFullWiki" @click.prevent="showingFullWiki = !showingFullWiki">
                    Full Wiki
                </button>
            </div>

            <section class="track-listing" v-if="album.info.tracks.length">
                <h1>Track Listing</h1>
                <ul class="tracks">
                    <li v-for="track in album.info.tracks">
                        <span class="no">{{ $index + 1 }}</span>
                        <span class="title">{{ track.title }}</span>
                        <span class="length">{{ track.fmtLength }}</span>
                    </li>
                </ul>
            </section>

            <footer>Data &copy; <a target="_blank" href="{{{ album.info.url }}}">Last.fm</a></footer>
        </div>

        <p class="none" v-else>No album information found. At all.</p>
    </article>
</template>

<script>
    import playback from '../../../services/playback';

    export default {
        replace: false,
        props: ['album'],

        data() {
            return {
                showingFullWiki: false,
            };
        },

        methods: {
            /**
             * Reset the component's current state.
             */
            resetState() {
                this.showingFullWiki = false;
            },

            /**
             * Shuffle all songs in the current album.
             */
            shuffleAll() {
                playback.playAllInAlbum(this.album);
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    #albumInfo {
        img.cover {
            width: 100%;
            height: auto;
        }

        .wiki {
            margin-top: 16px;
        }

        .track-listing {
            margin-top: 16px;

            h1 {
                font-size: 20px;
                margin-bottom: 0;
                display: block;
            }

            li {
                display: flex;
                justify-content: space-between;
                padding: 8px;

                &:nth-child(even) {
                    background: rgba(255, 255, 255, 0.05);
                }

                .no {
                    flex: 0 0 24px;
                    opacity: .5;
                }

                .title {
                    flex: 1;
                }

                .length {
                    flex: 0 0 48px;
                    text-align: right;
                    opacity: .5;
                }
            }
        }
    }
</style>

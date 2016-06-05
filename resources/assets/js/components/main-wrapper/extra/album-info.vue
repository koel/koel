<template>
    <article v-if="album.id" id="albumInfo" :class="mode">
        <h1 class="name">
            <span>{{ album.name }}</span>

            <a class="shuffle" @click.prevent="shuffleAll"><i class="fa fa-random"></i></a>
        </h1>

        <div v-if="album.info">
            <img v-if="album.info.image" :src="album.info.image"
                title=""
                class="cover">

            <div class="wiki" v-if="album.info.wiki && album.info.wiki.summary">
                <div class="summary" v-show="mode !== 'full' && !showingFullWiki">
                    {{{ album.info.wiki.summary }}}
                </div>
                <div class="full" v-show="mode === 'full' || showingFullWiki">
                    {{{ album.info.wiki.full }}}
                </div>

                <button class="more" v-show="mode !== 'full' && !showingFullWiki"
                    @click.prevent="showingFullWiki = !showingFullWiki">
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

        <p class="none" v-else>No album information found.</p>
    </article>
</template>

<script>
    import playback from '../../../services/playback';

    export default {
        replace: false,
        props: ['album', 'mode'],

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
    @import "../../../../sass/partials/_vars.scss";
    @import "../../../../sass/partials/_mixins.scss";

    #albumInfo {
        @include artist-album-info();
    }
</style>

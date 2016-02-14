<template>
    <article class="item" v-if="artist.songCount">
        <span class="cover" :style="{ backgroundImage: 'url(' + artist.image + ')' }">
            <a class="control" @click.prevent="play">
                <i class="fa fa-play"></i>
            </a>
        </span>
        <footer>
            <a class="name" @click.prevent="viewDetails">{{ artist.name }}</a>
            <p class="meta">
                {{ artist.albums.length }} {{ artist.albums.length | pluralize 'album' }}
                •
                {{ artist.songCount }} {{ artist.songCount | pluralize 'song' }}
                •
                {{ artist.playCount }} {{ artist.playCount | pluralize 'play' }}
            </p>
        </footer>
    </article>
</template>

<script>
    import playback from '../../services/playback';
    import artistStore from '../../stores/artist';
    import queueStore from '../../stores/queue';

    export default {
        props: ['artist'],

        methods: {
            /**
             * Play all songs by the current artist, or queue them up if Ctrl/Cmd key is pressed.
             */
            play($e) {
                if ($e.metaKey || $e.ctrlKey) {
                    queueStore.queue(artistStore.getSongsByArtist(this.artist));
                } else {
                    playback.playAllByArtist(this.artist);
                }
            },

            viewDetails() {
                this.$root.loadArtist(this.artist);
            },
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    @include artist-album-card();
</style>

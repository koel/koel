<template>
    <article class="item" v-if="album.songs.length">
        <span class="cover" :style="{ backgroundImage: 'url(' + album.cover + ')' }">
            <a class="control" @click.prevent="play">
                <i class="fa fa-play"></i>
            </a>
        </span>
        <footer>
            <a class="name" @click.prevent="viewDetails">{{ album.name }}</a>
            <a class="artist" @click.prevent="viewArtistDetails">{{ album.artist.name }}</a>
            <p class="meta">
                {{ album.songs.length }} {{ album.songs.length | pluralize 'song' }}
                •
                {{ album.fmtLength }}
                •
                {{ album.playCount }} {{ album.playCount | pluralize 'play' }}
            </p>
        </footer>
    </article>
</template>

<script>
    import playback from '../../services/playback';
    import queueStore from '../../stores/queue';

    export default {
        props: ['album'],

        methods: {
            /**
             * Play all songs in the current album, or queue them up if Ctrl/Cmd key is pressed.
             */
            play($e) {
                if ($e.metaKey || $e.ctrlKey) {
                    queueStore.queue(this.album.songs);
                } else {
                    playback.playAllInAlbum(this.album);
                }
            },

            /**
             * Load the album details screen.
             */
            viewDetails() {
                this.$root.loadAlbum(this.album);
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

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";

    @include artist-album-card();
</style>

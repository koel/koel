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
            </p>
        </footer>
    </article>
</template>

<script>
    import playback from '../../services/playback';

    export default {
        props: ['album'],

        methods: {
            /**
             * Play all songs in the current album.
             */
            play() {
                playback.playAllInAlbum(this.album);
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

    a.name, a.artist {
        display: block;
        color: $colorMainText;

        &:hover {
            color: $colorHighlight;
        }
    }
</style>

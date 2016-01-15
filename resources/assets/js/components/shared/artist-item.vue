<template>
    <article class="item" v-if="artist.songCount">
        <span class="cover" :style="{ backgroundImage: 'url('+artist.image+')' }">
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
            </p>
        </footer>
    </article>
</template>

<script>
    import playback from '../../services/playback';
    import artistStore from '../../stores/artist';

    export default {
        props: ['artist'],

        methods: {
            /**
             * Play all songs by the current artist.
             */
            play() {
                playback.playAllByArtist(this.artist);
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

    a.name {
        display: block;
        color: $colorMainText;

        &:hover {
            color: $colorHighlight;
        }
    }
</style>

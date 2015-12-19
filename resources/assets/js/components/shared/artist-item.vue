<template>
    <article class="item" v-if="artist.songCount">
        <span class="cover" :style="{ backgroundImage: 'url('+artist.cover+')' }">
            <a class="control" @click.prevent="play">
                <i class="fa fa-play"></i>
            </a>
        </span>
        <footer>
            <p class="name">{{ artist.name }}</p>
            <p class="meta">
                {{ artist.albums.length }} album{{ artist.albums.length == 1 ? '' : 's' }} – 
                {{ artist.songCount }} song{{ artist.songCount == 1 ? '' : 's' }}
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
        },
    };
</script>

<style lang="sass">
    @import "resources/assets/sass/partials/_vars.scss";
    @import "resources/assets/sass/partials/_mixins.scss";
</style>

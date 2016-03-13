<template>
    <article class="item" v-if="artist.songCount" draggable="true" @dragstart="dragStart">
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
    import _ from 'lodash';
    import $ from 'jquery';

    import playback from '../../services/playback';
    import artistStore from '../../stores/artist';
    import queueStore from '../../stores/queue';

    export default {
        props: ['artist'],

        methods: {
            /**
             * Play all songs by the current artist, or queue them up if Ctrl/Cmd key is pressed.
             */
            play(e) {
                if (e.metaKey || e.ctrlKey) {
                    queueStore.queue(artistStore.getSongsByArtist(this.artist));
                } else {
                    playback.playAllByArtist(this.artist);
                }
            },

            viewDetails() {
                this.$root.loadArtist(this.artist);
            },

            /**
             * Allow dragging the artist (actually, their songs).
             */
            dragStart(e) {
                var songIds = _.pluck(artistStore.getSongsByArtist(this.artist), 'id');
                e.dataTransfer.setData('text/plain', songIds);
                e.dataTransfer.effectAllowed = 'move';

                // Set a fancy drop image using our ghost element.
                var $ghost = $('#dragGhost').text(`All ${songIds.length} song${songIds.length === 1 ? '' : 's'} by ${this.artist.name}`);
                e.dataTransfer.setDragImage($ghost[0], 0, 0);
            },
        },
    };
</script>

<style lang="sass">
    @import "../../../sass/partials/_vars.scss";
    @import "../../../sass/partials/_mixins.scss";

    @include artist-album-card();
</style>

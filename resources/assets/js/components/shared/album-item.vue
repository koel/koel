<template>
    <article class="item" v-if="album.songs.length" draggable="true" @dragstart="dragStart">
        <span class="cover" :style="{ backgroundImage: 'url(' + album.cover + ')' }">
            <a class="control" @click.prevent="play">
                <i class="fa fa-play"></i>
            </a>
        </span>
        <footer>
            <a class="name" @click.prevent="viewDetails">{{ album.name }}</a>
            <span class="sep">by</span>
            <a class="artist" v-if="isNormalArtist" @click.prevent="viewArtistDetails">{{ album.artist.name }}</a>
            <span class="artist nope" v-else>{{ album.artist.name }}</span>
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
    import { map } from 'lodash';
    import $ from 'jquery';

    import playback from '../../services/playback';
    import queueStore from '../../stores/queue';
    import artistStore from '../../stores/artist';

    export default {
        props: ['album'],

        computed: {
            isNormalArtist() {
                return !artistStore.isVariousArtists(this.album.artist)
                    && !artistStore.isUnknownArtist(this.album.artist);
            },
        },

        methods: {
            /**
             * Play all songs in the current album, or queue them up if Ctrl/Cmd key is pressed.
             */
            play(e) {
                if (e.metaKey || e.ctrlKey) {
                    queueStore.queue(this.album.songs);
                } else {
                    playback.playAllInAlbum(this.album, false);
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

            /**
             * Allow dragging the album (actually, its songs).
             */
            dragStart(e) {
                const songIds = map(this.album.songs, 'id');
                e.dataTransfer.setData('text/plain', songIds);
                e.dataTransfer.effectAllowed = 'move';

                // Set a fancy drop image using our ghost element.
                const $ghost = $('#dragGhost').text(`All ${songIds.length} song${songIds.length === 1 ? '' : 's'} in ${this.album.name}`);
                e.dataTransfer.setDragImage($ghost[0], 0, 0);
            },
        },
    };
</script>

<style lang="sass">
    @import "../../../sass/partials/_vars.scss";
    @import "../../../sass/partials/_mixins.scss";

    @include artist-album-card();

    .sep {
        display: none;
        color: $color2ndText;

        .as-list & {
            display: inline;
        }
    }
</style>

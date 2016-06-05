<template>
    <article class="item" v-if="showing" draggable="true" @dragstart="dragStart">
        <span class="cover" :style="{ backgroundImage: 'url(' + artist.image + ')' }">
            <a class="control" @click.prevent="play">
                <i class="fa fa-play"></i>
            </a>
        </span>
        <footer>
            <a class="name" @click.prevent="viewArtistDetails(artist)">{{ artist.name }}</a>
            <p class="meta">
                <span class="left">
                    {{ artist.albums.length }} {{ artist.albums.length | pluralize 'album' }}
                    •
                    {{ artist.songCount }} {{ artist.songCount | pluralize 'song' }}
                    •
                    {{ artist.playCount }} {{ artist.playCount | pluralize 'play' }}
                </span>
                <span class="right">
                    <a href="#" @click.prevent="download" v-if="sharedState.allowDownload" title="Download all songs by artist">
                        <i class="fa fa-download"></i>
                    </a>
                </span>
            </p>
        </footer>
    </article>
</template>

<script>
    import { map } from 'lodash';
    import $ from 'jquery';

    import playback from '../../services/playback';
    import download from '../../services/download';
    import artistStore from '../../stores/artist';
    import queueStore from '../../stores/queue';
    import sharedStore from '../../stores/shared';
    import artistAlbumDetails from '../../mixins/artist-album-details';

    export default {
        props: ['artist'],
        mixins: [artistAlbumDetails],

        data() {
            return {
                sharedState: sharedStore.state,
            };
        },

        computed: {
            /**
             * Determine if the artist item should be shown.
             * We're not showing those without any songs, or the special "Various Artists".
             *
             * @return {Boolean}
             */
            showing() {
                return this.artist.songCount && !artistStore.isVariousArtists(this.artist);
            }
        },

        methods: {
            /**
             * Play all songs by the current artist, or queue them up if Ctrl/Cmd key is pressed.
             */
            play(e) {
                if (e.metaKey || e.ctrlKey) {
                    queueStore.queue(this.artist.songs);
                } else {
                    playback.playAllByArtist(this.artist);
                }
            },

            /**
             * Download all songs by artist.
             */
            download() {
                download.fromArtist(this.artist);
            },

            /**
             * Allow dragging the artist (actually, their songs).
             */
            dragStart(e) {
                const songIds = map(this.artist.songs, 'id');
                e.dataTransfer.setData('text/plain', songIds);
                e.dataTransfer.effectAllowed = 'move';

                // Set a fancy drop image using our ghost element.
                const $ghost = $('#dragGhost').text(`All ${songIds.length} song${songIds.length === 1 ? '' : 's'} by ${this.artist.name}`);
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

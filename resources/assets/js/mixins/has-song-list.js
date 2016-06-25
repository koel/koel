/**
 * Add necessary functionalities into a view that contains a song-list component.
 */

import { assign } from 'lodash';

import { event } from '../utils';
import { playback } from '../services';
import addToMenu from '../components/shared/add-to-menu.vue';
import songList from '../components/shared/song-list.vue';

export default {
    components: { addToMenu, songList },

    data() {
        return {
            /**
             * Whether or not to show the "Add To" button in the header.
             *
             * @type {Boolean}
             */
            showingAddToMenu: false,

            /**
             * An array of selected songs in the list.
             *
             * @type {Array.<Object>}
             */
            selectedSongs: [],

            meta: {
                songCount: 0,
                totalLength: '00:00',
            },
        };
    },

    methods: {
        /**
         * Shuffles the currently selected songs.
         */
        shuffleSelected() {
            if (this.selectedSongs.length < 2) {
                return;
            }

            playback.queueAndPlay(this.selectedSongs, true);
        },

        setSelectedSongs(songs) {
            this.selectedSongs = songs;
        },

        updateMeta(meta) {
            this.meta = assign(this.meta, meta);
        },

        closeAddToMenu() {
            this.showingAddToMenu = false;
        },
    },
};

/**
 * Add necessary functionalities into a view that contains a song-list component.
 */

import { assign } from 'lodash';

import { event } from '../utils';
import playback from '../services/playback';
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
    },

    created() {
        event.on({
            /**
             * Listen to add-to-menu:close event to set showingAddToMenu to false (and subsequently close the menu).
             */
            'add-to-menu:close': () => this.showingAddToMenu = false,

            'songlist:changed': meta => this.meta = assign(this.meta, meta),
        });
    },
};

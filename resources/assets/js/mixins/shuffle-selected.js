import $ from 'jquery';

import playback from '../services/playback';

/**
 * Add a "shuffle selected" functionality to any component containing a song-list component using this mixin.
 * Such a component should:
 * - pass "selectedSongs" SYNC prop into the song-list component, e.g.
 *     <song-list :items="state.songs" :selected-songs.sync="selectedSongs" type="queue"></song-list>
 * - trigger shuffling with shuffleSelected() method
 */
export default {
    data() {
        return {
            selectedSongs: [],
        };
    },

    methods: {
        shuffleSelected() {
            if (this.selectedSongs.length < 2) {
                return;
            }

            playback.queueAndPlay(this.selectedSongs, true);
        },
    },
};

/**
 * Add necessary functionalities into a view that contains a song-list component.
 */

import { assign } from 'lodash';
import isMobile from 'ismobilejs';

import { playback } from '../services';
import songList from '../components/shared/song-list.vue';
import songListControls from '../components/shared/song-list-controls.vue';

export default {
  components: { songList, songListControls },

  data() {
    return {
      state: null,
      meta: {
        songCount: 0,
        totalLength: '00:00',
      },
      selectedSongs: [],
      isPhone: isMobile.phone,
      showingControls: true,
      songListControlConfig: {},
    };
  },

  methods: {
    setSelectedSongs(songs) {
      this.selectedSongs = songs;
    },

    updateMeta(meta) {
      this.meta = assign(this.meta, meta);
    },

    shuffleAll() {
      playback.queueAndPlay(this.state.songs, true);
    },

    shuffleSelected() {
      playback.queueAndPlay(this.selectedSongs, true);
    },
  },
};

/**
 * Add necessary functionalities into a view that triggers artist or album details views.
 */

import { loadAlbumView, loadArtistView } from './../utils';

export default {
    methods: {
        /**
         * Load the album details screen.
         */
        viewAlbumDetails(album) {
            loadAlbumView(album);
        },

        /**
         * Load the artist details screen.
         */
        viewArtistDetails(artist) {
            loadArtistView(artist);
        },
    },
};

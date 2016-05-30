/**
 * Add necessary functionalities into a view that triggers artist or album details views.
 */

export default {
    methods: {
        /**
         * Load the album details screen.
         */
        viewAlbumDetails(album) {
            this.$root.loadAlbum(album);
        },

        /**
         * Load the artist details screen.
         */
        viewArtistDetails(artist) {
            this.$root.loadArtist(artist);
        },
    },
};

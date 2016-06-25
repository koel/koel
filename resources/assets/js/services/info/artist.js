import { http } from '..';

export const artistInfo = {
    /**
     * Get extra artist info (from Last.fm).
     *
     * @param  {Object}    artist
     * @param  {?Function} cb
     */
    fetch(artist, cb = null) {
        if (artist.info) {
            cb && cb();

            return;
        }

        http.get(`artist/${artist.id}/info`, response => {
            if (response.data) {
                this.merge(artist, response.data);
            }

            cb && cb();
        });
    },

    /**
     * Merge the (fetched) info into an artist.
     *
     * @param  {Object} artist
     * @param  {Object} info
     */
    merge(artist, info) {
        // If the artist image is not in a nice form, discard.
        if (typeof info.image !== 'string') {
            info.image = null;
        }

        // Set the artist image on the client side to the retrieved image from server.
        if (info.image) {
            artist.image = info.image;
        }

        artist.info = info;
    },
};

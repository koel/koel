import _ from 'lodash';

import config from '../config';
import albumStore from './album';

export default {
    state: {
        artists: [],
    },

    /**
     * Init the store.
     * 
     * @param  {Array} artists The array of artists we got from the server.
     */
    init(artists) {
        this.state.artists = artists;

        // Traverse through artists array to get the cover and number of songs for each.
        _.each(this.state.artists, artist => {
            this.getImage(artist);

            artist.songCount = _.reduce(artist.albums, (count, album)  => count + album.songs.length, 0);
        });

        albumStore.init(this.state.artists);
    },

    all() {
        return this.state.artists;
    },

    /**
     * Get all songs performed by an artist.
     *
     * @param {Object} artist
     *
     * @return {Array}
     */
    getSongsByArtist(artist) {
        if (!artist.songs) {
            artist.songs = _.reduce(artist.albums, (songs, album) => songs.concat(album.songs), []);
        }

        return artist.songs;
    },

    /**
     * Get the artist's image.
     *
     * @param {Object} artist
     *
     * @return {String}
     */
    getImage(artist) {
        // If the artist already has a proper image, just return it.
        if (artist.image) {
            return artist.image;
        }

        // Otherwise, we try to get an image from one of their albums.
        artist.image = config.unknownCover;

        artist.albums.every(album => {
            // If there's a "real" cover, use it.
            if (album.image != config.unknownCover) {
                artist.image = album.cover;
                
                // I want to break free.
                return false;
            }
        });

        return artist.image;
    },
};

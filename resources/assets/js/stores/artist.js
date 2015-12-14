import _ from 'lodash';

import config from '../config';
import albumStore from './album';
import sharedStore from './shared';

export default {
    state: {
        artists: [],
    },

    /**
     * Init the store.
     * 
     * @param  array artists The array of artists we got from the server.
     */
    init(artists = null) {
        this.state.artists = artists ? artists: sharedStore.state.artists;

        // Init the album store. This must be called prior to the next logic,
        // because we're using some data from the album store later.
        albumStore.init(this.state.artists);

        // Traverse through artists array to get the cover and number of songs for each.
        _.each(this.state.artists, artist => {
            this.getCover(artist);
            
            artist.songCount = _.reduce(artist.albums, (count, album)  => count + album.songs.length, 0);
        });
    },

    all() {
        return this.state.artists;
    },

    /**
     * Get all songs performed by an artist.
     *
     * @param object artist
     *
     * @return array
     */
    getSongsByArtist(artist) {
        if (!artist.songs) {
            artist.songs = _.reduce(artist.albums, (songs, album) => songs.concat(album.songs), []);
        }

        return artist.songs;
    },

    /**
     * Get the artist's cover
     *
     * @param object artist
     *
     * @return string
     */
    getCover(artist) {
        artist.cover = config.unknownCover;

        artist.albums.every(album => {
            // If there's a "real" cover, use it.
            if (album.cover != config.unknownCover) {
                artist.cover = album.cover;
                
                // I want to break free.
                return false;
            }
        });

        return artist.cover;
    },
};

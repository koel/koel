import _ from 'lodash';

import utils from '../services/utils';
import stub from '../stubs/album';
import songStore from './song';

export default {
    stub,
    artists: [],
    
    state: {
        albums: [stub],    
    },

    /**
     * Init the store.
     * 
     * @param  array artists The array of artists to extract album data from.
     */
    init(artists) {
        this.artists = artists;

        // Traverse through the artists array and add their albums into our master album list.
        this.state.albums = _.reduce(artists, (albums, artist) => {
            // While we're doing so, for each album, we get its length
            // and keep a back reference to the artist too.
            _.each(artist.albums, album => {
                album.artist = artist;
                this.getLength(album);
            });

            return albums.concat(artist.albums);
        }, []);

        // Then we init the song store.
        songStore.init(this.state.albums);
    },

    all() {
        return this.state.albums;
    },

    /**
     * Get the total length of an album by summing up its songs' duration.
     * The length will also be converted into a H:i:s format and stored as fmtLength.
     * 
     * @param  object album
     * 
     * @return string The H:i:s format of the album.
     */
    getLength(album) {
        album.length = _.reduce(album.songs, (length, song) => length + song.length, 0);

        return (album.fmtLength = utils.secondsToHis(album.length));
    },
};

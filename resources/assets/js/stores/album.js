import _ from 'lodash';

import utils from '../services/utils';
import stub from '../stubs/album';
import songStore from './song';

export default {
    stub,

    state: {
        albums: [stub],
    },

    /**
     * Init the store.
     *
     * @param  {Array.<Object>} artists The array of artists to extract album data from.
     */
    init(artists) {
        // Traverse through the artists array and add their albums into our master album list.
        this.state.albums = _.reduce(artists, (albums, artist) => {
            // While we're doing so, for each album, we get its length
            // and keep a back reference to the artist too.
            _.each(artist.albums, album => {
                Vue.set(album, 'playCount', 0);
                Vue.set(album, 'artist', artist);
                Vue.set(album, 'info', null);
                this.getLength(album);
            });

            return albums.concat(artist.albums);
        }, []);

        // Then we init the song store.
        songStore.init(this.state.albums);
    },

    /**
     * Get all albums in the store.
     *
     * @return {Array.<Object>}
     */
    all() {
        return this.state.albums;
    },

    /**
     * Get the total length of an album by summing up its songs' duration.
     * The length will also be converted into a H:i:s format and stored as fmtLength.
     *
     * @param  {Object} album
     *
     * @return {String} The H:i:s format of the album length.
     */
    getLength(album) {
        Vue.set(album, 'length', _.reduce(album.songs, (length, song) => length + song.length, 0));
        Vue.set(album, 'fmtLength', utils.secondsToHis(album.length));

        return album.fmtLength;
    },

    /**
     * Get top n most-played albums.
     *
     * @param  {Number} n
     *
     * @return {Array.<Object>}
     */
    getMostPlayed(n = 6) {
        var albums = _.take(_.sortByOrder(this.state.albums, 'playCount', 'desc'), n);

        // Remove those with playCount=0
        _.remove(albums, album => !album.playCount);

        return albums;
    },
};

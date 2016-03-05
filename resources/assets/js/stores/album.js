import Vue from 'vue';
import _ from 'lodash';

import utils from '../services/utils';
import stub from '../stubs/album';
import songStore from './song';
import artistStore from './artist';

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
                this.setupAlbum(album, artist);
            });

            return albums.concat(artist.albums);
        }, []);

        // Then we init the song store.
        songStore.init(this.state.albums);
    },

    setupAlbum(album, artist) {
        Vue.set(album, 'playCount', 0);
        Vue.set(album, 'artist', artist);
        Vue.set(album, 'info', null);
        this.getLength(album);

        return album;
    },

    /**
     * Get all albums in the store.
     *
     * @return {Array.<Object>}
     */
    all() {
        return this.state.albums;
    },

    byId(id) {
        return _.find(this.all(), 'id', id);
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
     * Appends a new album into the current collection.
     *
     * @param  {Object} album
     */
    append(album) {
        this.state.albums.push(this.setupAlbum(album, album.artist));
        album.playCount = _.reduce(album.songs, (count, song) => count + song.playCount, 0);
    },

    /**
     * Add song(s) into an album.
     *
     * @param {Object} album
     * @param {Array.<Object>|Object} song
     */
    addSongsIntoAlbum(album, songs) {
        songs = [].concat(songs);

        album.songs = _.union(album.songs ? album.songs : [], songs);

        songs.forEach(song => {
            song.album_id = album.id;
            song.album = album;
        });

        album.playCount = _.reduce(album.songs, (count, song) => count + song.playCount, 0);
        this.getLength(album);
    },

    /**
     * Remove song(s) from an album.
     *
     * @param  {Object} album
     * @param  {Array.<Object>|Object} songs
     */
    removeSongsFromAlbum(album, songs) {
        album.songs = _.difference(album.songs, [].concat(songs));
        album.playCount = _.reduce(album.songs, (count, song) => count + song.playCount, 0);
        this.getLength(album);
    },

    /**
     * Checks if an album is empty.
     *
     * @param  {Object}  album
     *
     * @return {boolean}
     */
    isAlbumEmpty(album) {
        return !album.songs.length;
    },

    /**
     * Remove album(s) from the store.
     *
     * @param  {Array.<Object>|Object} albums
     */
    remove(albums) {
        albums = [].concat(albums);
        this.state.albums = _.difference(this.state.albums, albums);

        // Remove from the artist as well
        albums.forEach(album => {
            artistStore.removeAlbumsFromArtist(album.artist, album);
        });
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

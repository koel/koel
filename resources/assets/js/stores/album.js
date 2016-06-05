import Vue from 'vue';
import {
    reduce,
    each,
    find,
    union,
    difference,
    take,
    filter,
    orderBy
} from 'lodash';

import { secondsToHis } from '../services/utils';
import http from '../services/http';
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
        this.all = reduce(artists, (albums, artist) => {
            // While we're doing so, for each album, we get its length
            // and keep a back reference to the artist too.
            each(artist.albums, album => {
                this.setupAlbum(album, artist);
            });

            return albums.concat(artist.albums);
        }, []);

        // Then we init the song store.
        songStore.init(this.all);
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
    get all() {
        return this.state.albums;
    },

    /**
     * Set all albums.
     *
     * @param  {Array.<Object>} value
     */
    set all(value) {
        this.state.albums = value;
    },

    byId(id) {
        return find(this.all, { id });
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
        Vue.set(album, 'length', reduce(album.songs, (length, song) => length + song.length, 0));
        Vue.set(album, 'fmtLength', secondsToHis(album.length));

        return album.fmtLength;
    },

    /**
     * Add new album/albums into the current collection.
     *
     * @param  {Array.<Object>|Object} albums
     */
    add(albums) {
        albums = [].concat(albums);
        each(albums, a => {
            this.setupAlbum(a, a.artist)
            a.playCount = reduce(a.songs, (count, song) => count + song.playCount, 0);
        });

        this.all = union(this.all, albums);
    },

    /**
     * Add song(s) into an album.
     *
     * @param {Object} album
     * @param {Array.<Object>|Object} song
     */
    addSongsIntoAlbum(album, songs) {
        songs = [].concat(songs);

        album.songs = union(album.songs ? album.songs : [], songs);

        each(songs, song => {
            song.album_id = album.id;
            song.album = album;
        });

        album.playCount = reduce(album.songs, (count, song) => count + song.playCount, 0);
        this.getLength(album);
    },

    /**
     * Remove song(s) from an album.
     *
     * @param  {Object} album
     * @param  {Array.<Object>|Object} songs
     */
    removeSongsFromAlbum(album, songs) {
        album.songs = difference(album.songs, [].concat(songs));
        album.playCount = reduce(album.songs, (count, song) => count + song.playCount, 0);
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
        this.all = difference(this.all, albums);

        // Remove from the artist as well
        each(albums, album => {
            artistStore.removeAlbumsFromArtist(album.artist, album);
        });
    },

    /**
     * Get extra album info (from Last.fm).
     *
     * @param  {Object}    album
     * @param  {?Function} cb
     */
    fetchInfo(album, cb = null) {
        if (album.info) {
            cb && cb();

            return;
        }

        http.get(`album/${album.id}/info`, response => {
            if (response.data) {
                this.mergeAlbumInfo(album, response.data);
            }

            cb && cb();
        });
    },

    /**
     * Merge the (fetched) info into an album.
     *
     * @param  {Object} album
     * @param  {Object} info
     */
    mergeAlbumInfo(album, info) {
        // Convert the duration into i:s
        info.tracks && each(info.tracks, track => track.fmtLength = secondsToHis(track.length));

        // If the album cover is not in a nice form, discard.
        if (typeof info.image !== 'string') {
            info.image = null;
        }

        // Set the album cover on the client side to the retrieved image from server.
        if (info.cover) {
            album.cover = info.cover;
        }

        album.info = info;
    },

    /**
     * Get top n most-played albums.
     *
     * @param  {Number} n
     *
     * @return {Array.<Object>}
     */
    getMostPlayed(n = 6) {
        // Only non-unknown albums with actually play count are applicable.
        const applicable = filter(this.all, album => {
            return album.playCount && album.id !== 1;
        });

        return take(orderBy(applicable, 'playCount', 'desc'), n);
    },
};

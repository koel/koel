import Vue from 'vue';
import {
    reduce,
    each,
    find,
    union,
    difference,
    take,
    filter,
    sortByOrder
} from 'lodash';

import config from '../config';
import stub from '../stubs/artist';
import albumStore from './album';

export default {
    stub,

    state: {
        artists: [],
    },

    /**
     * Init the store.
     *
     * @param  {Array.<Object>} artists The array of artists we got from the server.
     */
    init(artists) {
        this.state.artists = artists;

        // Traverse through artists array to get the cover and number of songs for each.
        each(this.state.artists, artist => {
            this.setupArtist(artist);
        });

        albumStore.init(this.state.artists);
    },

    setupArtist(artist) {
        this.getImage(artist);
        Vue.set(artist, 'playCount', 0);
        Vue.set(artist, 'songCount', reduce(artist.albums, (count, album) => count + album.songs.length, 0));
        Vue.set(artist, 'info', null);

        return artist;
    },

    get all() {
        return this.state.artists;
    },

    byId(id) {
        return find(this.all, 'id', id);
    },

    /**
     * Appends a new artist into the current collection.
     *
     * @param  {Object} artist
     */
    append(artist) {
        this.state.artists.push(this.setupArtist(artist));
    },

    addAlbumsIntoArtist(artist, albums) {
        albums = [].concat(albums);

        artist.albums = union(artist.albums ? artist.albums : [], albums);

        albums.forEach(album => {
            album.artist_id = artist.id;
            album.artist = artist;
        });

        artist.playCount = reduce(artist.albums, (count, album) => count + album.playCount, 0);
    },

    /**
     * Remove album(s) from an artist.
     *
     * @param  {Object} artist
     * @param  {Array.<Object>|Object} albums
     */
    removeAlbumsFromArtist(artist, albums) {
        artist.albums = difference(artist.albums, [].concat(albums));
        artist.playCount = reduce(artist.albums, (count, album) => count + album.playCount, 0);
    },

    /**
     * Checks if an artist is empty.
     *
     * @param  {Object}  artist
     *
     * @return {boolean}
     */
    isArtistEmpty(artist) {
        return !artist.albums.length;
    },

    /**
     * Remove artist(s) from the store.
     *
     * @param  {Array.<Object>|Object} artists
     */
    remove(artists) {
        this.state.artists = difference(this.state.artists, [].concat(artists));
    },

    /**
     * Get all songs performed by an artist.
     *
     * @param {Object} artist
     *
     * @return {Array.<Object>}
     */
    getSongsByArtist(artist) {
        if (!artist.songs) {
            artist.songs = reduce(artist.albums, (songs, album) => songs.concat(album.songs), []);
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
            if (album.image !== config.unknownCover) {
                artist.image = album.cover;

                // I want to break free.
                return false;
            }
        });

        return artist.image;
    },

    /**
     * Get top n most-played artists.
     *
     * @param  {Number} n
     *
     * @return {Array.<Object>}
     */
    getMostPlayed(n = 6) {
        // Only non-unknown artists with actually play count are applicable.
        const applicable = filter(this.state.artists, artist => {
            return artist.playCount && artist.id !== 1;
        });

        return take(sortByOrder(applicable, 'playCount', 'desc'), n);
    },
};

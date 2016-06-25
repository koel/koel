import $ from 'jquery';
import { map } from 'lodash';

import { playlistStore, favoriteStore } from '../stores';
import { ls } from '.';

export const download = {
    /**
     * Download individual song(s).
     *
     * @param {Array.<Object>|Object} songs
     */
    fromSongs(songs) {
        songs = [].concat(songs);
        const ids = map(songs, 'id');
        const params = $.param({ songs: ids });

        return this.trigger(`songs?${params}`);
    },

    /**
     * Download all songs in an album.
     *
     * @param {Object} album
     */
    fromAlbum(album) {
        return this.trigger(`album/${album.id}`);
    },

    /**
     * Download all songs performed by an artist.
     *
     * @param {Object} artist
     */
    fromArtist(artist) {
        // It's safe to assume an artist always has songs.
        // After all, what's an artist without her songs?
        // (See what I did there? Yes, I'm advocating for women's rights).
        return this.trigger(`artist/${artist.id}`);
    },

    /**
     * Download all songs in a playlist.
     *
     * @param {Object} playlist
     */
    fromPlaylist(playlist) {
        if (!playlistStore.getSongs(playlist).length) {
            console.warn('Empty playlist.');
            return;
        }

        return this.trigger(`playlist/${playlist.id}`);
    },

    /**
     * Download all favorite songs.
     */
    fromFavorites() {
        if (!favoriteStore.all.length) {
            console.warn("You don't like any song? Come on, don't be that grumpy.");
            return;
        }

        return this.trigger('favorites');
    },

    /**
     * Build a download link using a segment and trigger it.
     *
     * @param  {string} uri The uri segment, corresponding to the song(s),
     *                      artist, playlist, or album.
     */
    trigger(uri) {
        const sep = uri.indexOf('?') === -1 ? '?' : '&';
        const frameId = `downloader${Date.now()}`;
        $(`<iframe id="${frameId}" style="display:none"></iframe`).appendTo('body');
        document.getElementById(frameId).src = `/api/download/${uri}${sep}jwt-token=${ls.get('jwt-token')}`;
    },
}

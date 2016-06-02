import $ from 'jquery';
import { map } from 'lodash';

import playlistStore from '../stores/playlist';
import ls from './ls';

export default {
    fromSongs(songs) {
        const ids = map(songs, 'id');
        const params = $.param({ songs: ids });

        return this.trigger(`songs?${params}`);
    },

    fromAlbum(album) {

    },

    fromArtist(artist) {

    },

    fromPlaylist(playlist) {
        if (!playlistStore.getSongs(playlist).length) {
            console.warn('Empty playlist.');
            return;
        }

        return this.trigger(`playlist/${playlist.id}`);
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

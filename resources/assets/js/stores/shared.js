import http from '../services/http';
import { assign } from 'lodash';

export default {
    state: {
        songs: [],
        albums: [],
        artists: [],
        favorites: [],
        queued: [],
        interactions: [],
        users: [],
        settings: [],
        currentUser: null,
        playlists: [],
        useLastfm: false,
    },

    init(cb = null) {
        http.get('data', {}, data => {
            assign(this.state, data);

            // If this is a new user, initialize his preferences to be an empty object.
            if (!this.state.currentUser.preferences) {
                this.state.currentUser.preferences = {};
            }

            if (cb) {
                cb();
            }
        });
    },
};

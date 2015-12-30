import { assign } from 'lodash';

import http from '../services/http';
import userStore from './user';
import preferenceStore from './preference';
import artistStore from './artist';
import songStore from './song';
import playlistStore from './playlist';
import queueStore from './queue';
import settingStore from './setting';

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
        currentVersion: '',
        latestVersion: '',
    },

    init(successCb = null, errorCb = null) {
        this.reset();
        
        http.get('data', data => {
            assign(this.state, data);

            // If this is a new user, initialize his preferences to be an empty object.
            if (!this.state.currentUser.preferences) {
                this.state.currentUser.preferences = {};
            }

            userStore.init(this.state.users, this.state.currentUser);
            preferenceStore.init(this.state.preferences);
            artistStore.init(this.state.artists); // This will init album and song stores as well.
            songStore.initInteractions(this.state.interactions);
            playlistStore.init(this.state.playlists);
            queueStore.init();
            settingStore.init(this.state.settings);

            window.useLastfm = this.state.useLastfm = data.useLastfm;
        }, successCb, errorCb);
    },

    reset() {
        this.state.songs = [];
        this.state.albums = [];
        this.state.artists = [];
        this.state.favorites = [];
        this.state.queued = [];
        this.state.interactions = [];
        this.state.users = [];
        this.state.settings = [];
        this.state.currentUser = null;
        this.state.playlists = [];
        this.state.useLastfm = false;
        this.state.currentVersion = '';
        this.state.latestVersion = '';
    },
};

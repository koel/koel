import http from '../services/http';

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
    },

    init(cb = null) {
        http.get('data', {}, data => {
            this.state.songs = data.songs;
            this.state.artists = data.artists;
            this.state.albums = data.albums;
            this.state.settings = data.settings;
            this.state.playlists = data.playlists;
            this.state.interactions = data.interactions;
            this.state.users = data.users;
            this.state.currentUser = data.user;
            this.state.settings = data.settings;

            if (cb) {
                cb();
            }
        });
    },
};

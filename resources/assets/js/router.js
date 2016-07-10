import { loadMainView } from './utils';
import { artistStore, albumStore, songStore, playlistStore } from './stores';

export default {
  routes: {
    '/home'() {
      loadMainView('home');
    },

    '/queue'() {
      loadMainView('queue');
    },

    '/songs'() {
      loadMainView('songs');
    },

    '/albums'() {
      loadMainView('albums');
    },

    '/album/(\\d+)'(id) {
      const album = albumStore.byId(Number.parseInt(id, 10));
      if (album) {
        loadMainView('album', album);
      }
    },

    '/artists'() {
      loadMainView('artists');
    },

    '/artist/(\\d+)'(id) {
      const artist = artistStore.byId(Number.parseInt(id, 10));
      if (artist) {
        loadMainView('artist', artist);
      }
    },

    '/favorites'() {
      loadMainView('favorites');
    },

    '/playlist/(\\d+)'(id) {
      const playlist = playlistStore.byId(Number.parseInt(id, 10));
      if (playlist) {
        loadMainView('playlist', playlist);
      }
    },

    '/settings'() {
      loadMainView('settings');
    },

    '/users'() {
      loadMainView('users');
    },

    '/profile'() {
      loadMainView('profile');
    },

    '/login'() {

    },

    '/song/([a-z0-9]{32})'(id) {
      console.log(id)
    },
  },

  init() {
    this.loadState();
    window.addEventListener('popstate', () => this.loadState(), true);
  },

  loadState() {
    if (!window.location.hash) {
      return this.go('/#!/home');
    }

    Object.keys(this.routes).forEach(route => {
      const matches = window.location.hash.match(new RegExp(`^#!${route}$`));
      if (matches) {
        const [, ...params] = matches;
        this.routes[route](...params);
        return false;
      }
    });
  },

  /**
   * Navigate to a (relative) path.
   *
   * @param  {String} path
   */
  go(path) {
    if (path[0] !== '/') {
      path = `/${path}`;
    }
    document.location.href = `${document.location.origin}${path}`;
  },
};

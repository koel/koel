import isMobile from 'ismobilejs';

import { loadMainView } from './utils';
import { artistStore, albumStore, songStore, queueStore, playlistStore } from './stores';
import { playback } from './services';

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
      const song = songStore.byId(id);
      if (!song) return;

      if (isMobile.apple.device) {
        // Mobile Safari doesn't allow autoplay, so we just queue.
        queueStore.queue(song);
        loadMainView('queue');
      } else {
        playback.queueAndPlay(song);
      }
    },

    '/youtube'() {
      loadMainView('youtubePlayer');
    },
  },

  init() {
    this.loadState();
    window.addEventListener('popstate', () => this.loadState(), true);
  },

  loadState() {
    if (!window.location.hash) {
      return this.go('home');
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
   * Navigate to a (relative, hashed) path.
   *
   * @param  {String} path
   */
  go(path) {
    if (path[0] !== '/') {
      path = `/${path}`;
    }

    if (path.indexOf('/#!') !== 0) {
      path = `/#!${path}`;
    }

    document.location.href = `${document.location.origin}${path}`;
  },
};

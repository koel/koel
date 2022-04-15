import isMobile from 'ismobilejs'

import { loadMainView } from './utils'
import { artistStore, albumStore, songStore, queueStore, playlistStore, userStore } from './stores'
import { playback } from './services'
import { use } from '@/utils'

const router = {
  routes: {
    '/home': (): void => loadMainView('Home'),
    '/queue': (): void => loadMainView('Queue'),
    '/songs': (): void => loadMainView('Songs'),
    '/albums': (): void => loadMainView('Albums'),
    '/artists': (): void => loadMainView('Artists'),
    '/favorites': (): void => loadMainView('Favorites'),
    '/recently-played': (): void => loadMainView('RecentlyPlayed'),
    '/search': (): void => loadMainView('Search.Excerpt'),
    '/search/songs/(.+)': (q: string) => loadMainView('Search.Songs', q),

    '/upload': (): void => {
      if (userStore.current.is_admin) {
        loadMainView('Upload')
      }
    },

    '/settings': (): void => {
      if (userStore.current.is_admin) {
        loadMainView('Settings')
      }
    },

    '/users': (): void => {
      if (userStore.current.is_admin) {
        loadMainView('Users')
      }
    },

    '/youtube': (): void => loadMainView('YouTube'),
    '/visualizer': (): void => loadMainView('Visualizer'),
    '/profile': (): void => loadMainView('Profile'),

    '/album/(\\d+)': (id: number) => use(albumStore.byId(~~id)!, (album: Album): void => {
      loadMainView('Album', album)
    }),

    '/artist/(\\d+)': (id: number) => use(artistStore.byId(~~id)!, (artist: Artist): void => {
      loadMainView('Artist', artist)
    }),

    '/playlist/(\\d+)': (id: number) => use(playlistStore.byId(~~id)!, (playlist: Playlist): void => {
      loadMainView('Playlist', playlist)
    }),

    '/song/([a-z0-9]{32})': (id: string): void => use(songStore.byId(id)!, (song: Song): void => {
      if (isMobile.apple.device) {
        // Mobile Safari doesn't allow autoplay, so we just queue.
        queueStore.queue(song)
        loadMainView('Queue')
      } else {
        playback.queueAndPlay([song])
      }
    })
  } as { [path: string]: Function },

  init (): void {
    this.loadState()
    window.addEventListener('popstate', (): void => this.loadState(), true)
  },

  loadState (): void {
    if (!window.location.hash) {
      return this.go('home')
    }

    Object.keys(this.routes).forEach((route: string): void => {
      const matches = window.location.hash.match(new RegExp(`^#!${route}$`))

      if (matches) {
        const [, ...params] = matches
        this.routes[route](...params)
      }
    })
  },

  /**
   * Navigate to a (relative, hash-bang'ed) path.
   */
  go: (path: string | number): void => {
    if (window.__UNIT_TESTING__) {
      return
    }

    if (typeof path === 'number') {
      window.history.go(path)
      return
    }

    if (path[0] !== '/') {
      path = `/${path}`
    }

    if (path.indexOf('/#!') !== 0) {
      path = `/#!${path}`
    }

    path = path.substring(1, path.length)
    document.location.href = `${document.location.origin}${document.location.pathname}${path}`
  }
}

export default router

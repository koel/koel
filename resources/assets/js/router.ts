import isMobile from 'ismobilejs'

import { loadMainView } from './utils'
import { albumStore, artistStore, playlistStore, queueStore, songStore, userStore } from './stores'
import { playbackService } from './services'
import { use } from '@/utils'

const router = {
  routes: {
    '/home': () => loadMainView('Home'),
    '/queue': () => loadMainView('Queue'),
    '/songs': () => loadMainView('Songs'),
    '/albums': () => loadMainView('Albums'),
    '/artists': () => loadMainView('Artists'),
    '/favorites': () => loadMainView('Favorites'),
    '/recently-played': () => loadMainView('RecentlyPlayed'),
    '/search': () => loadMainView('Search.Excerpt'),
    '/search/songs/(.+)': (q: string) => loadMainView('Search.Songs', q),
    '/upload': () => userStore.current.is_admin && loadMainView('Upload'),
    '/settings': () => userStore.current.is_admin && loadMainView('Settings'),
    '/users': () => userStore.current.is_admin && loadMainView('Users'),
    '/youtube': () => loadMainView('YouTube'),
    '/visualizer': () => loadMainView('Visualizer'),
    '/profile': () => loadMainView('Profile'),
    '/album/(\\d+)': (id: number) => use(albumStore.byId(~~id)!, album => loadMainView('Album', album)),
    '/artist/(\\d+)': (id: number) => use(artistStore.byId(~~id)!, artist => loadMainView('Artist', artist)),
    '/playlist/(\\d+)': (id: number) => use(playlistStore.byId(~~id)!, playlist => loadMainView('Playlist', playlist)),
    '/song/([a-z0-9]{32})': (id: string) => use(songStore.byId(id)!, song => {
      if (isMobile.apple.device) {
        // Mobile Safari doesn't allow autoplay, so we just queue.
        queueStore.queue(song)
        loadMainView('Queue')
      } else {
        playbackService.queueAndPlay([song])
      }
    })
  } as { [path: string]: Closure },

  init () {
    this.loadState()
    window.addEventListener('popstate', () => this.loadState(), true)
  },

  loadState () {
    if (!window.location.hash) {
      return this.go('home')
    }

    Object.keys(this.routes).forEach(route => {
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
  go: (path: string | number) => {
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

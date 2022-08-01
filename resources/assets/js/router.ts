import { eventBus, loadMainView, use } from '@/utils'
import { playlistStore, userStore } from '@/stores'

class Router {
  routes: Record<string, Closure>

  constructor () {
    this.routes = {
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
      '/album/(\\d+)': async (id: string) => loadMainView('Album', parseInt(id)),
      '/artist/(\\d+)': async (id: string) => loadMainView('Artist', parseInt(id)),
      '/playlist/(\\d+)': (id: number) => use(playlistStore.byId(~~id), playlist => loadMainView('Playlist', playlist)),
      '/song/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})': (id: string) => {
        eventBus.emit('SONG_QUEUED_FROM_ROUTE', id)
        loadMainView('Queue')
      }
    }

    window.addEventListener('popstate', () => this.resolveRoute(), true)
  }

  public resolveRoute () {
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
  }

  /**
   * Navigate to a (relative, hash-bang'ed) path.
   */
  public go (path: string | number) {
    if (typeof path === 'number') {
      window.history.go(path)
      return
    }

    if (!path.startsWith('/')) {
      path = `/${path}`
    }

    if (!path.startsWith('/#!')) {
      path = `/#!${path}`
    }

    path = path.substring(1, path.length)
    document.location.href = `${document.location.origin}${document.location.pathname}${path}`
  }
}

export default new Router()
